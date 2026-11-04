// Categoría (tipo_producto_padres) y subcategoría (tipo_productos) de los gastos
// de Ventas y de las cajas. Mismo criterio que categoriaGastoTexto() del backend.

/** GET /categorias-gasto: [{ id, nombre, subcategorias: [{ id, nombre }] }] (solo activas). */
export async function cargarCategoriasGasto (axios) {
  const { data } = await axios.get('categorias-gasto')
  return data || []
}

/**
 * "CATEGORÍA / SUBCATEGORÍA", solo la categoría si no tiene subcategoría, o
 * "Sin categoría" en los gastos anteriores al campo, con su texto antiguo
 * (columna `categoria` de las cajas) entre paréntesis si lo tiene.
 */
export function textoCategoriaGasto (registro) {
  const categoria = registro?.categoria_gasto?.nombre
  if (categoria) {
    const sub = registro?.subcategoria_gasto?.nombre
    return sub ? `${categoria} / ${sub}` : categoria
  }
  const anterior = String(registro?.categoria || '').trim()
  return anterior ? `Sin categoría (${anterior})` : 'Sin categoría'
}
