<?php

namespace App\Support;

/**
 * Catálogo único de permisos del sistema, agrupados por módulo.
 * Lo usan el seeder y las migraciones para mantener sincronizada
 * la tabla `permissions` (incluida su columna `modulo`).
 */
class Permisos
{
    public const MODULOS = [
        'Dashboard' => [
            'Ver Dashboard',
        ],
        'Usuarios' => [
            'Ver Usuarios', 'Crear Usuarios', 'Editar Usuarios', 'Eliminar Usuarios',
            'Gestionar Permisos',
        ],
        'Pacientes' => [
            'Ver Pacientes', 'Crear Pacientes', 'Editar Pacientes', 'Eliminar Pacientes',
        ],
        'Internaciones' => [
            'Ver Internaciones', 'Crear Internaciones', 'Editar Internaciones', 'Eliminar Internaciones',
        ],
        'Doctores' => [
            'Ver Doctores', 'Crear Doctores', 'Editar Doctores', 'Eliminar Doctores',
        ],
        'Farmacia' => [
            'Ver Productos', 'Crear Productos', 'Editar Productos', 'Eliminar Productos',
        ],
        'Productos Farmacia' => [
            'Ver Productos Farmacia', 'Crear Productos Farmacia',
            'Editar Productos Farmacia', 'Eliminar Productos Farmacia',
        ],
        'Vencimientos' => [
            'Ver Productos por Vencer', 'Ver Productos Vencidos',
        ],
        'Compras' => [
            'Ver Compras', 'Crear Compras', 'Editar Compras', 'Eliminar Compras',
        ],
        'Ventas' => [
            'Ver Ventas', 'Crear Ventas', 'Editar Ventas', 'Eliminar Ventas',
            'Ver Montos Caja',
        ],
        'Caja' => [
            'Ver Cierres Caja', 'Cerrar Caja',
        ],
        'Caja Administrativa' => [
            'Ver Caja Administrativa', 'Crear Caja Administrativa',
            'Editar Caja Administrativa', 'Anular Caja Administrativa',
        ],
        'Caja General' => [
            'Ver Caja General', 'Crear Caja General',
            'Editar Caja General', 'Anular Caja General',
        ],
        'Seguros' => [
            'Ver Seguros', 'Crear Seguros', 'Editar Seguros', 'Eliminar Seguros',
        ],
        'Laboratorio' => [
            'Ver Solicitudes Laboratorio', 'Crear Solicitudes Laboratorio',
            'Editar Solicitudes Laboratorio', 'Eliminar Solicitudes Laboratorio',
        ],
        'Reportes' => [
            'Ver Reportes', 'Imprimir Resultados', 'Exportar Excel',
        ],
        'Configuracion' => [
            'Ver Configuracion', 'Editar Configuracion',
        ],
    ];

    /** Todos los nombres de permiso, sin agrupar. */
    public static function todos(): array
    {
        return array_merge(...array_values(self::MODULOS));
    }

    /** Módulo al que pertenece un permiso ('Otros' si no está catalogado). */
    public static function moduloDe(string $permiso): string
    {
        foreach (self::MODULOS as $modulo => $permisos) {
            if (in_array($permiso, $permisos, true)) {
                return $modulo;
            }
        }

        return 'Otros';
    }
}
