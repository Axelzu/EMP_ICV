const test = require('node:test');
const assert = require('node:assert');
const { parsearReporte } = require('../parser_reporte');

test('interpreta un reporte en inglés con tóner', () => {
  const r = parsearReporte('Serial Number: KM-A1001\nTotal Black: 120,500\nTotal Color: 31.200\nToner Level: 55%');
  assert.deepStrictEqual(r, { serie: 'KM-A1001', contador_bn: 120500, contador_color: 31200, toner_pct: 55, fecha: null });
});

test('interpreta un reporte en español', () => {
  const r = parsearReporte('Número de serie: XR-B2002\nB/N: 98000\nColor: 0');
  assert.strictEqual(r.serie, 'XR-B2002');
  assert.strictEqual(r.contador_bn, 98000);
});

test('devuelve null si no hay número de serie', () => {
  assert.strictEqual(parsearReporte('Total Black: 100'), null);
});

test('devuelve null si no hay contador', () => {
  assert.strictEqual(parsearReporte('Serial Number: AB-1234'), null);
});
