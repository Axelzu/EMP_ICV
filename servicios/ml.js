// Núcleo analítico (RF-06): proyección del contador mensual con series temporales usando brain.js.
// Se evalúan tres candidatos por equipo (red LSTM, perceptrón multicapa con ventana de rezagos y promedio móvil de referencia)
// y se usa el que obtenga menor error absoluto medio (MAE) en un backtest sobre los últimos meses reales.
const brain = require('brain.js');

const MESES_MIN_FIABLE = 6; // por debajo de este historial el resultado se marca como "preliminar"

function ymToIdx(ym) { const [y, m] = ym.split('-').map(Number); return y * 12 + (m - 1); }
function idxToYm(i) { const y = Math.floor(i / 12); const m = (i % 12) + 1; return `${y}-${String(m).padStart(2, '0')}`; }

// lecturas: [{fecha:'YYYY-MM-DD HH:MM:SS', valor:Number}] ordenadas. Devuelve [{ym, valor}] con el último valor de cada mes,
// interpolando linealmente los meses sin lectura para que la serie sea continua.
function construirSerieMensual(lecturas) {
  const porMes = new Map();
  for (const l of lecturas) porMes.set(l.fecha.slice(0, 7), Number(l.valor));
  const claves = [...porMes.keys()].sort();
  if (!claves.length) return [];
  const out = [];
  for (let i = 0; i < claves.length; i++) {
    const cur = ymToIdx(claves[i]);
    if (i > 0) {
      const prev = ymToIdx(claves[i - 1]);
      const gap = cur - prev;
      const v0 = porMes.get(claves[i - 1]), v1 = porMes.get(claves[i]);
      for (let g = 1; g < gap; g++) out.push({ ym: idxToYm(prev + g), valor: v0 + ((v1 - v0) * g) / gap, interpolado: true });
    }
    out.push({ ym: claves[i], valor: porMes.get(claves[i]) });
  }
  return out;
}

function consumos(serie) {
  const c = [];
  for (let i = 1; i < serie.length; i++) c.push(Math.max(0, serie[i].valor - serie[i - 1].valor));
  return c;
}

// ---- Modelos: cada uno recibe el historial de consumos y devuelve el consumo esperado de los próximos `pasos` meses ----
function modeloPromedioMovil(c, pasos) {
  const v = c.slice(-3);
  const m = v.reduce((a, b) => a + b, 0) / v.length;
  return Array(pasos).fill(m);
}

function modeloMLP(c, pasos) {
  const escala = Math.max(...c, 1);
  const n = c.map(x => x / escala);
  const w = Math.min(3, n.length - 1);
  const datos = [];
  for (let i = w; i < n.length; i++) datos.push({ input: n.slice(i - w, i), output: [n[i]] });
  const red = new brain.NeuralNetwork({ hiddenLayers: [6] });
  red.train(datos, { iterations: 1500, errorThresh: 0.0005, learningRate: 0.3, log: false });
  const ventana = n.slice(-w);
  const out = [];
  for (let s = 0; s < pasos; s++) {
    const p = Math.max(0, red.run(ventana)[0]);
    out.push(p * escala);
    ventana.push(p); ventana.shift();
  }
  return out;
}

function modeloLSTM(c, pasos) {
  const escala = Math.max(...c, 1);
  const n = c.map(x => x / escala);
  const red = new brain.recurrent.LSTMTimeStep({ inputSize: 1, hiddenLayers: [8], outputSize: 1 });
  red.train([n], { iterations: 400, errorThresh: 0.002, learningRate: 0.01, log: false });
  const f = red.forecast(n, pasos);
  return f.map(x => Math.max(0, (Array.isArray(x) ? x[0] : x) * escala));
}

const MODELOS = [
  { nombre: 'brain.js-LSTM', fn: modeloLSTM, minimo: 4 },
  { nombre: 'brain.js-MLP', fn: modeloMLP, minimo: 4 },
  { nombre: 'promedio-movil', fn: modeloPromedioMovil, minimo: 1 }
];

// MAE (%) del modelo sobre los últimos meses reales: se entrena sin ese mes y se compara contra lo ocurrido.
function backtest(modelo, c) {
  const ks = Math.min(3, c.length - 3);
  if (ks < 1) return null;
  let suma = 0, cnt = 0;
  for (let k = 1; k <= ks; k++) {
    const entrenamiento = c.slice(0, c.length - k);
    const real = c[c.length - k];
    if (real <= 0) continue;
    const pred = modelo.fn(entrenamiento, 1)[0];
    suma += Math.abs(pred - real) / real;
    cnt++;
  }
  return cnt ? (suma / cnt) * 100 : null;
}

// Proyecta el contador acumulado al cierre de `periodoObjetivo` ('YYYY-MM').
// Se usa solo el historial anterior a ese mes (lo que se sabría si el equipo no reporta).
function proyectarSerie(lecturas, periodoObjetivo) {
  const serie = construirSerieMensual(lecturas).filter(p => p.ym < periodoObjetivo);
  if (serie.length < 2) return null;
  const c = consumos(serie);
  const pasos = ymToIdx(periodoObjetivo) - ymToIdx(serie[serie.length - 1].ym);

  let mejor = null;
  for (const m of MODELOS) {
    if (c.length < m.minimo) continue;
    let mae = null;
    try { mae = backtest(m, c); } catch (e) { continue; }
    if (mejor === null || (mae !== null && (mejor.mae === null || mae < mejor.mae))) mejor = { modelo: m, mae };
  }
  if (!mejor) mejor = { modelo: MODELOS[2], mae: null };

  const consumoEsperado = mejor.modelo.fn(c, pasos);
  const ultimo = serie[serie.length - 1].valor;
  const estimado = Math.round(ultimo + consumoEsperado.reduce((a, b) => a + b, 0));
  return {
    estimado,
    mae: mejor.mae,
    modelo: mejor.modelo.nombre,
    meses: serie.length,
    preliminar: serie.length < MESES_MIN_FIABLE,
    consumoMensual: consumoEsperado[0],
    ultimoValor: ultimo
  };
}

module.exports = { construirSerieMensual, consumos, proyectarSerie, ymToIdx, idxToYm, MESES_MIN_FIABLE };
