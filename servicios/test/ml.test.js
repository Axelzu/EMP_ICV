const test = require('node:test');
const assert = require('node:assert');
const { construirSerieMensual, proyectarSerie, consumos } = require('../ml');

function lecturasLineales(meses, base, porMes) {
  const out = [];
  for (let i = 0; i < meses; i++) {
    const mes = String(i + 1).padStart(2, '0');
    out.push({ fecha: `2025-${mes}-28 08:00:00`, valor: base + i * porMes });
  }
  return out;
}

test('interpola los meses sin lectura', () => {
  const s = construirSerieMensual([{ fecha: '2025-01-28 00:00:00', valor: 1000 }, { fecha: '2025-04-28 00:00:00', valor: 4000 }]);
  assert.deepStrictEqual(s.map(p => p.valor), [1000, 2000, 3000, 4000]);
});

test('el consumo nunca es negativo', () => {
  const c = consumos([{ ym: '2025-01', valor: 100 }, { ym: '2025-02', valor: 90 }]);
  assert.deepStrictEqual(c, [0]);
});

test('proyecta un consumo constante con error bajo', () => {
  const r = proyectarSerie(lecturasLineales(9, 10000, 3000), '2025-10');
  assert.ok(r, 'debe haber proyección');
  assert.ok(Math.abs(r.estimado - (10000 + 9 * 3000)) / (10000 + 9 * 3000) < 0.02, `estimado ${r.estimado}`);
  assert.strictEqual(r.preliminar, false);
});

test('marca como preliminar con menos de 6 meses de historial', () => {
  const r = proyectarSerie(lecturasLineales(4, 10000, 3000), '2025-05');
  assert.strictEqual(r.preliminar, true);
});

test('sin historial suficiente no proyecta', () => {
  assert.strictEqual(proyectarSerie(lecturasLineales(1, 10000, 3000), '2025-03'), null);
});
