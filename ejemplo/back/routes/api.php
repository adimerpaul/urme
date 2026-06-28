<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AreaRangoController;
use App\Http\Controllers\AlmacenItemController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\EstablecimientoController;
use App\Http\Controllers\FormulariosController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\PartidaController;
use App\Http\Controllers\ConsentimientoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoPorVencerController;
use App\Http\Controllers\ProductoVencidoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SolicitudeController;
use App\Http\Controllers\SubpartidaController;
use App\Http\Controllers\UnidadSolicitanteController;
use App\Http\Controllers\AreaTipoMuestraController; // <-- NUEVO
use App\Http\Controllers\SolicitudePropiedadController;
use App\Http\Controllers\SolicitudCatalogoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteServiciosController;
use App\Http\Controllers\RecogidoController;
use App\Http\Controllers\SolicitudSapController;
use App\Http\Controllers\UnidadController;
use App\Http\Controllers\HerramientasAlmacenController;
use App\Http\Controllers\HerramientaUsuarioController;
use App\Http\Controllers\DespachoController;
use App\Http\Controllers\ReporteValoradoController;
use App\Http\Controllers\ReporteResumenDetalleController;
use App\Http\Controllers\ReporteUnidadController;

Route::post('/login', [App\Http\Controllers\UserController::class, 'login']);
Route::post('/register', [App\Http\Controllers\UserController::class, 'register']);
Route::get('/unidades-public', [App\Http\Controllers\UnidadController::class, 'index']);
Route::get('/username-preview', [App\Http\Controllers\UserController::class, 'usernamePreview']);
Route::get('/registro-estado', [App\Http\Controllers\UserController::class, 'registroEstado']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/me', [App\Http\Controllers\UserController::class, 'me']);
    Route::post('/logout', [App\Http\Controllers\UserController::class, 'logout']);
    Route::put('/perfil', [App\Http\Controllers\UserController::class, 'updatePerfil']);
    Route::put('/cambiar-password', [App\Http\Controllers\UserController::class, 'changePassword']);

    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy']);
    Route::put('/updatePassword/{user}', [App\Http\Controllers\UserController::class, 'updatePassword']);
    Route::put('/resetPassword/{user}', [App\Http\Controllers\UserController::class, 'resetPassword']);
    Route::post('/{user}/avatar', [App\Http\Controllers\UserController::class, 'updateAvatar']);
    Route::post('/{user}/firma',  [App\Http\Controllers\UserController::class, 'updateFirma']);
    Route::post('/{user}/sello',  [App\Http\Controllers\UserController::class, 'updateSello']);
    Route::get('/permissions', [App\Http\Controllers\UserController::class, 'permissions']);
    Route::get('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'userPermissions']);
    Route::put('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'updateUserPermissions']);
    Route::get('/users/{user}/subpartidas', [App\Http\Controllers\UserController::class, 'userSubpartidas']);
    Route::put('/users/{user}/subpartidas', [App\Http\Controllers\UserController::class, 'syncUserSubpartidas']);

    // Pacientes
    Route::get('pacientes/buscar-ci/{ci}',      [PacienteController::class, 'buscarPorCi']);
    Route::get('pacientes/nn-rn/{tipo}',         [PacienteController::class, 'buscarPorTipoNN_RN']);
    Route::get('pacientes/{id}/historico',       [PacienteController::class, 'historico']);
    Route::get('pacientes/{id}/historico/excel', [PacienteController::class, 'historicoExcel']);
    Route::apiResource('pacientes', PacienteController::class);

    // Consentimientos
    Route::apiResource('consentimientos', ConsentimientoController::class);
    Route::get('solicitudes/{id}/consentimiento', [ConsentimientoController::class, 'showBySolicitude']);
    Route::post('solicitudes/{id}/consentimiento', [ConsentimientoController::class, 'upsertBySolicitude']);
    Route::get('doctores/export/excel', [DoctorController::class, 'exportarExcel']);
    Route::get('doctores/export/pdf',   [DoctorController::class, 'exportarPdf']);
    Route::apiResource('doctores', DoctorController::class);

    Route::get('solicitudes/export/excel', [SolicitudeController::class, 'indexExcel']);
    Route::get('solicitudes/export/pdf',   [SolicitudeController::class, 'indexPdf']);
    Route::apiResource('solicitudes', SolicitudeController::class);
    Route::get('solicitudes-create-catalogos', [SolicitudCatalogoController::class, 'create']);

    Route::get('establecimientos/export/excel', [EstablecimientoController::class, 'exportarExcel']);
    Route::get('establecimientos/export/pdf',   [EstablecimientoController::class, 'exportarPdf']);
    Route::apiResource('establecimientos', EstablecimientoController::class);
    Route::apiResource('equipos', \App\Http\Controllers\EquipoController::class);
    Route::apiResource('unidad-solicitantes', UnidadSolicitanteController::class);
    Route::apiResource('unidades', UnidadController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('proveedores', ProveedorController::class);

    Route::apiResource('grupos', GrupoController::class);
    Route::apiResource('partidas', PartidaController::class);
    Route::apiResource('subpartidas', SubpartidaController::class);
    Route::get('almacen-items/reporte/pdf', [AlmacenItemController::class, 'reportPdf']);
    Route::get('compras-mayo/sin-vincular/count', [\App\Http\Controllers\ComprasMayoController::class, 'count']);
    Route::get('compras-mayo/sin-vincular', [\App\Http\Controllers\ComprasMayoController::class, 'sinVincular']);
    Route::get('compras-mayo/buscar-item', [\App\Http\Controllers\ComprasMayoController::class, 'buscarItem']);
    Route::post('compras-mayo/{id}/vincular', [\App\Http\Controllers\ComprasMayoController::class, 'vincular']);
    Route::post('compras-mayo/{id}/desvincular', [\App\Http\Controllers\ComprasMayoController::class, 'desvincular']);
    Route::get('almacen-items/reporte/excel', [AlmacenItemController::class, 'reportExcel']);
    Route::get('reportes/almacen-dashboard', [AlmacenItemController::class, 'dashboard']);
    Route::apiResource('almacen-items', AlmacenItemController::class);
    Route::post('almacen-items/{id}/imagen', [AlmacenItemController::class, 'updateImagen']);
    Route::get('almacen/productos-por-vencer', [ProductoPorVencerController::class, 'index']);
    Route::get('almacen/productos-vencidos', [ProductoVencidoController::class, 'index']);
    Route::get('compras/{id}/pdf', [CompraController::class, 'printPdf']);
    Route::apiResource('compras', CompraController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::get('pedidos/limite-mensual', [PedidoController::class, 'limiteMensual']);
    Route::get('pedidos/{id}/pdf', [PedidoController::class, 'printPdf']);
    Route::patch('pedidos/{id}/unidad', [PedidoController::class, 'cambiarUnidad']);
    Route::apiResource('pedidos', PedidoController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('solicitudes-sap', SolicitudSapController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::get('solicitudes-sap/{id}/pdf', [SolicitudSapController::class, 'printPdf']);
    Route::get('herramientas-almacen', [HerramientasAlmacenController::class, 'index']);
    Route::put('herramientas-almacen', [HerramientasAlmacenController::class, 'update']);
    Route::get('herramientas-usuario', [HerramientaUsuarioController::class, 'index']);
    Route::put('herramientas-usuario', [HerramientaUsuarioController::class, 'update']);
    Route::get('reporte-valorado', [ReporteValoradoController::class, 'index']);
    Route::get('reporte-valorado/pdf', [ReporteValoradoController::class, 'pdf']);
    Route::get('reporte-valorado/excel', [ReporteValoradoController::class, 'excel']);

    Route::get('reporte-resumen-detalle', [ReporteResumenDetalleController::class, 'index']);
    Route::get('reporte-resumen-detalle/resumen/pdf', [ReporteResumenDetalleController::class, 'resumenPdf']);
    Route::get('reporte-resumen-detalle/detalle/pdf', [ReporteResumenDetalleController::class, 'detallePdf']);
    Route::get('reporte-resumen-detalle/resumen/excel', [ReporteResumenDetalleController::class, 'resumenExcel']);
    Route::get('reporte-resumen-detalle/detalle/excel', [ReporteResumenDetalleController::class, 'detalleExcel']);

    Route::get('despachos/pedido-lookup/{id}', [DespachoController::class, 'pedidoLookup']);
    Route::get('despachos/{id}/pdf', [DespachoController::class, 'printPdf']);
    Route::post('despachos/{id}/anular', [DespachoController::class, 'anular']);
    Route::patch('despachos/{id}/unidad', [DespachoController::class, 'cambiarUnidad']);
    Route::apiResource('despachos', DespachoController::class)->only(['index', 'show', 'store', 'destroy']);

    Route::apiResource('areas', AreaController::class);
//    areasCreateSolicitud
    Route::get('areasCreateSolicitud', [AreaController::class, 'areasCreateSolicitud']);
    Route::apiResource('servicios', ServicioController::class);
    Route::post('servicios/{id}/tipos-muestra', [ServicioController::class, 'syncTiposMuestra']);

    Route::get('solicitudes-area-preanalitica', [SolicitudeController::class, 'solicitudesAreaPreanalitica']);
    Route::get('solicitudes-area-preanalitica-estado', [SolicitudeController::class, 'solicitudesAreaPreanaliticaEstado']);
    Route::post('solicitudes/{id}/pre-analitica', [SolicitudeController::class, 'guardarPreAnalitica']);
    Route::post('solicitudes/{id}/pre-analitica-comentarios', [SolicitudeController::class, 'storePreAnaliticaComentario']);
    Route::delete('solicitudes/{id}/pre-analitica-comentarios/{comentarioId}', [SolicitudeController::class, 'destroyPreAnaliticaComentario']);
    Route::get('areas-tipo-muestras', [AreaController::class, 'tipoMuestras']);

    Route::post('solicitudes/{id}/generar-codigo', [SolicitudeController::class, 'generarCodigo']);
    Route::get('solicitudesMuestrasRechazadas', [SolicitudeController::class, 'muestrasRechazadas']);
    Route::post('solicitudes/{id}/actualizar-codigo', [SolicitudeController::class, 'actualizarCodigo']);
    Route::get('solicitudes/{id}/test-embarazo', [SolicitudeController::class, 'showTestEmbarazo']);
    Route::post('solicitudes/{id}/test-embarazo', [SolicitudeController::class, 'saveTestEmbarazo']);
    Route::get('solicitudes/{id}/test-embarazo/pdf', [SolicitudeController::class, 'printTestEmbarazo']);

//    Route::get('solicitudes-area-analitica', [SolicitudeController::class, 'solicitudesAreaAnalitica']);
//    Route::post('solicitudes/{id}/analitica', [SolicitudeController::class, 'guardarAnalitica']);

    Route::apiResource('area-rangos', AreaRangoController::class);

    // NUEVO: CRUD de tipos de muestra por área
    Route::apiResource('area-tipo-muestras', AreaTipoMuestraController::class);

    Route::get('solicitudes-area-analitica', [SolicitudeController::class, 'solicitudesAreaAnalitica']);
    Route::get('solicitudes-area-analitica/{id}', [SolicitudeController::class, 'showAnalitica']); // NUEVA
    Route::post('solicitudes/{id}/analitica', [SolicitudeController::class, 'guardarAnalitica']);

    Route::apiResource('solicitude-propiedades', SolicitudePropiedadController::class);

//    analitica/solicitudes
    Route::get('solicitudesAnalitica', [SolicitudeController::class, 'solicitudesAnalitica']);
    Route::get('analitica/para-presentacion', [SolicitudeController::class, 'paraPresentacion']);
    Route::post('analitica/registrar-presentacion', [SolicitudeController::class, 'registrarPresentacion']);

    Route::get   ('formularios',        [FormulariosController::class, 'index']);
    Route::post  ('formularios',        [FormulariosController::class, 'store']);
    Route::get   ('formularios/{id}',   [FormulariosController::class, 'show']);
    Route::put   ('formularios/{id}',   [FormulariosController::class, 'update']);
    Route::delete('formularios/{id}',   [FormulariosController::class, 'destroy']);

    Route::get('reportes/solicitudes-dashboard',       [SolicitudeController::class, 'dashboard']);
    Route::get('reportes/solicitudes-dashboard/list',  [SolicitudeController::class, 'dashboardList']);
    Route::get('reportes/solicitudes-dashboard/excel', [SolicitudeController::class, 'dashboardExcel']);

    Route::get('/hematologia/solicitud/{id}', [\App\Http\Controllers\HematologiaController::class, 'showBySolicitude']);
    Route::post('/hematologia/solicitud/{id}', [\App\Http\Controllers\HematologiaController::class, 'upsert']);
    Route::delete('/hematologia/solicitud/{id}', [\App\Http\Controllers\HematologiaController::class, 'destroyBySolicitude']);

    Route::get('quimica-sanguinea/solicitud/{id}', [\App\Http\Controllers\QuimicaSanguineaController::class, 'showBySolicitude']);
    Route::post('quimica-sanguinea/solicitud/{id}', [\App\Http\Controllers\QuimicaSanguineaController::class, 'upsert']);
    Route::delete('quimica-sanguinea/solicitud/{id}', [\App\Http\Controllers\QuimicaSanguineaController::class, 'destroyBySolicitude']);

    Route::get('uroanalisis/solicitud/{id}', [\App\Http\Controllers\UroanalisisController::class, 'showBySolicitude']);
    Route::post('uroanalisis/solicitud/{id}', [\App\Http\Controllers\UroanalisisController::class, 'upsert']);
    Route::delete('uroanalisis/solicitud/{id}', [\App\Http\Controllers\UroanalisisController::class, 'destroyBySolicitude']);

    Route::get('parasitologia/solicitud/{id}', [\App\Http\Controllers\ParasitologiaController::class, 'showBySolicitude']);
    Route::post('parasitologia/solicitud/{id}', [\App\Http\Controllers\ParasitologiaController::class, 'upsert']);
    Route::delete('parasitologia/solicitud/{id}', [\App\Http\Controllers\ParasitologiaController::class, 'destroyBySolicitude']);

    Route::get   ('diagnosticos',      [\App\Http\Controllers\DiagnosticoController::class, 'index']);
    Route::post  ('diagnosticos',      [\App\Http\Controllers\DiagnosticoController::class, 'store']);
    Route::put   ('diagnosticos/{id}', [\App\Http\Controllers\DiagnosticoController::class, 'update']);
    Route::delete('diagnosticos/{id}', [\App\Http\Controllers\DiagnosticoController::class, 'destroy']);
    Route::get('papiloma-humano/solicitud/{id}', [\App\Http\Controllers\PapilomaHumanoController::class, 'showBySolicitude']);
    Route::post('papiloma-humano/solicitud/{id}', [\App\Http\Controllers\PapilomaHumanoController::class, 'upsert']);
    Route::delete('papiloma-humano/solicitud/{id}', [\App\Http\Controllers\PapilomaHumanoController::class, 'destroyBySolicitude']);

    Route::get('/panel-respiratorio/solicitud/{id}', [\App\Http\Controllers\PanelRespiratorioController::class, 'showBySolicitude']);
    Route::post('/panel-respiratorio/solicitud/{id}', [\App\Http\Controllers\PanelRespiratorioController::class, 'upsert']);
    Route::delete('/panel-respiratorio/solicitud/{id}', [\App\Http\Controllers\PanelRespiratorioController::class, 'destroyBySolicitude']);

    Route::get('/panel-sexual/solicitud/{id}', [\App\Http\Controllers\PanelSexualController::class, 'showBySolicitude']);
    Route::post('/panel-sexual/solicitud/{id}', [\App\Http\Controllers\PanelSexualController::class, 'upsert']);
    Route::delete('/panel-sexual/solicitud/{id}', [\App\Http\Controllers\PanelSexualController::class, 'destroyBySolicitude']);

    Route::get('/cultivo-antibiograma/solicitud/{id}', [\App\Http\Controllers\CultivoAntibiogramaController::class, 'showBySolicitude']);
    Route::post('/cultivo-antibiograma/solicitud/{id}', [\App\Http\Controllers\CultivoAntibiogramaController::class, 'upsert']);
    Route::delete('/cultivo-antibiograma/solicitud/{id}', [\App\Http\Controllers\CultivoAntibiogramaController::class, 'destroyBySolicitude']);

    Route::get('/inmunologia/solicitud/{id}', [\App\Http\Controllers\InmunologiaController::class, 'dashboard']);
    Route::post('/inmunologia/solicitud/{id}/add', [\App\Http\Controllers\InmunologiaController::class, 'add']);

    Route::put('/inmunologia/solicitude-formulario/{id}', [\App\Http\Controllers\InmunologiaController::class, 'update']);
    Route::delete('/inmunologia/solicitude-formulario/{id}', [\App\Http\Controllers\InmunologiaController::class, 'remove']);

    // Inmunología analítica estructurada (basada en rangos vinculados a prestaciones)
    Route::get('/inmunologia-analitica/solicitud/{id}', [\App\Http\Controllers\InmunologiaAnaliticaController::class, 'show']);
    Route::post('/inmunologia-analitica/solicitud/{id}/resultados', [\App\Http\Controllers\InmunologiaAnaliticaController::class, 'saveResultados']);

    // Vincular rangos a una prestación/servicio
    Route::get('/servicios/{id}/rangos', [\App\Http\Controllers\ServicioController::class, 'getRangos']);
    Route::post('/servicios/{id}/rangos', [\App\Http\Controllers\ServicioController::class, 'syncRangos']);

    // Fórmulas derivadas de una prestación
    Route::get('/servicios/{id}/formulas', [\App\Http\Controllers\ServicioController::class, 'getFormulas']);
    Route::post('/servicios/{id}/formulas', [\App\Http\Controllers\ServicioController::class, 'syncFormulas']);

    Route::get('reportes/almacen-unidad/unidades', [ReporteUnidadController::class, 'unidades']);
    Route::get('reportes/almacen-unidad/personas', [ReporteUnidadController::class, 'personas']);
    Route::get('reportes/almacen-unidad', [ReporteUnidadController::class, 'index']);
    Route::get('reportes/almacen-unidad/excel', [ReporteUnidadController::class, 'exportExcel']);
    Route::get('reportes/almacen-unidad/pdf', [ReporteUnidadController::class, 'exportPdf']);

    Route::get('reportes/servicios-resumen', [ReporteServiciosController::class, 'index']);
    Route::get('reportes/servicios-resumen/excel', [ReporteServiciosController::class, 'exportExcel']);
    Route::get('reportes/servicios-resumen/excel-mensual', [ReporteServiciosController::class, 'exportExcelMensual']);
    Route::get('reportes/servicios-resumen/pdf', [ReporteServiciosController::class, 'exportPdf']);
    Route::get('reportes/servicios-resumen/excel-ents', [ReporteServiciosController::class, 'exportExcelEnts']);
    Route::get('reportes/servicios-resumen/pdf-ents', [ReporteServiciosController::class, 'exportPdfEnts']);
    Route::get('reportes/consentimientos', [ConsentimientoController::class, 'reporte']);
    Route::get('reportes/solicitudes-servicios', [SolicitudeController::class, 'reporteSolicitudesServicios']);

    Route::post('solicitudes/{id}/marcar-muestra-no-tomada', [SolicitudeController::class, 'marcarMuestraNoTomada']);

    Route::get('recogidos', [RecogidoController::class, 'index']);
    Route::put('recogidos/{id}', [RecogidoController::class, 'update']);
    Route::post('recogidos/recoger-area', [RecogidoController::class, 'recogerArea']);
    Route::get('reportes/recogidos/pdf', [RecogidoController::class, 'reportePdf']);

    Route::get('entrega-resultados', [\App\Http\Controllers\EntregaResultadoController::class, 'index']);
    Route::post('entrega-resultados/registrar', [\App\Http\Controllers\EntregaResultadoController::class, 'registrar']);

});
Route::get('solicitudes-area-preanalitica/pdf', [SolicitudeController::class, 'pdfPreanalitica']);
Route::get('analitica/pdf-presentacion', [SolicitudeController::class, 'pdfPresentacion']);
Route::get('solicitudes/{id}/analitica-pdf', [SolicitudeController::class, 'imprimirAnalitica']);
Route::get('public/reportes/{codigo}', [SolicitudeController::class, 'imprimirAnaliticaPublica'])
    ->name('solicitudes.analitica.publica');

Route::get('consentimientos/{id}/print', [ConsentimientoController::class, 'print']);
Route::get('solicitudes/{id}/consentimiento/print', [ConsentimientoController::class, 'printBySolicitude']);
//pacientes/nn-rn/
Route::get('pacientesnn-rn/', [PacienteController::class, 'buscarPorNN_RN']);

//pdfBySolicitude
Route::get('/hematologia/solicitud/{id}/pdf', [\App\Http\Controllers\HematologiaController::class, 'pdfBySolicitude']);
Route::get('parasitologia/solicitud/{id}/pdf', [\App\Http\Controllers\ParasitologiaController::class, 'pdfBySolicitude']);
Route::get('uroanalisis/solicitud/{id}/pdf', [\App\Http\Controllers\UroanalisisController::class, 'pdfBySolicitude']);
Route::get('/quimica-sanguinea/solicitud/{id}/pdf', [\App\Http\Controllers\QuimicaSanguineaController::class, 'pdfBySolicitude']);
//const url = `${this.$axios.defaults.baseURL}/quimica-sanguinea/solicitud/${solicitud.quimica_sanguinea?.code}/pdf-tolerancia`
Route::get('/quimica-sanguinea/solicitud/{id}/pdf-tolerancia', [\App\Http\Controllers\QuimicaSanguineaController::class, 'pdfToleranciaBySolicitude']);
//const url = `${this.$axios.defaults.baseURL}/quimica-sanguinea/solicitud/${solicitud.quimica_sanguinea?.code}/pdf-cito-quimico`
Route::get('/quimica-sanguinea/solicitud/{id}/pdf-cito-quimico', [\App\Http\Controllers\QuimicaSanguineaController::class, 'pdfCitoQuimicoBySolicitude']);
Route::get('papiloma-humano/solicitud/{id}/pdf', [\App\Http\Controllers\PapilomaHumanoController::class, 'pdfBySolicitude']);
Route::get('/panel-respiratorio/solicitud/{id}/pdf', [\App\Http\Controllers\PanelRespiratorioController::class, 'pdfBySolicitude']);
Route::get('/panel-sexual/solicitud/{id}/pdf', [\App\Http\Controllers\PanelSexualController::class, 'pdfBySolicitude']);
Route::get('/cultivo-antibiograma/solicitud/{id}/pdf', [\App\Http\Controllers\CultivoAntibiogramaController::class, 'pdfBySolicitude']);

Route::get('/inmunologia/solicitude-formulario/{id}/pdf', [\App\Http\Controllers\InmunologiaController::class, 'pdfOne']);
Route::get('/inmunologia/solicitud/{id}/pdf-all', [\App\Http\Controllers\InmunologiaController::class, 'pdfAll']);
Route::get('/inmunologia-analitica/resultado/{codigo}/pdf', [\App\Http\Controllers\InmunologiaAnaliticaController::class, 'pdfBySolicitude']);
