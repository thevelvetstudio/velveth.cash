<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Casa',
                'code' => 'CASA',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Diseño de red internet / modems\nLobby: monitoreo de cámaras\nLlaves generales y acceso\nAcceso: chapa digital para puerta de vidrio\nModems\nDispensador de alcohol\nCortinería\nAmbientaciones",
            ],
            [
                'name' => 'Implementaciones de marca - Fase 1',
                'code' => 'MARCA-F1',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Impresos de marca\nLobby tapete\nAplicaciones de marca",
            ],
            [
                'name' => 'Implementaciones de marca - Fase 2',
                'code' => 'MARCA-F2',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => null,
            ],
            [
                'name' => 'Implementaciones de marca - Fase 3',
                'code' => 'MARCA-F3',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => null,
            ],
            [
                'name' => 'Lobby',
                'code' => 'LOBBY',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Dispensador comida\nDispensador café",
            ],
            [
                'name' => 'Journey admisión',
                'code' => 'ADMISION',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Website ingreso\nFormulario datos personales\nOnboarding autogestión de datos\nVerificación de datos\nSelección y entrevista\nIngreso del sistema dactilar y al sistema",
            ],
            [
                'name' => 'Kit de inicio',
                'code' => 'KIT-INICIO',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Bata\nToalla\nJuego lencería\nKit de aseo\nJuguete obligatorio\nReglamento interno\nLubricante",
            ],
            [
                'name' => 'Recomendaciones personales',
                'code' => 'REC-PER',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => null,
            ],
            [
                'name' => 'Monitoreo y oficinas',
                'code' => 'MON-OFI',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Rack internet y espacio de trabajo\nSala de descanso\nServidor general\nPC monitoreo: x1 computador monitor",
            ],
            [
                'name' => 'Recursos humanos',
                'code' => 'RRHH',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => 'PC de RR.HH admin proyecto seguimiento',
            ],
            [
                'name' => 'Baños',
                'code' => 'BANOS',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Mantenimiento\nHorno microondas",
            ],
            [
                'name' => 'Cocina',
                'code' => 'COCINA',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Nevera y accesorios\nUtilería de cocina - vajilla\nInventario",
            ],
            [
                'name' => 'Zona de lavado',
                'code' => 'LAVADO',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Lavadora secadora\nAlmacenamiento",
            ],
            [
                'name' => 'Zona de almacenamiento',
                'code' => 'ALMACEN',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "Estantería\nAlmacenamientos",
            ],
            [
                'name' => 'Zonas comunes',
                'code' => 'ZONAS-COM',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => null,
            ],
            [
                'name' => 'Cuartos - Tech',
                'code' => 'CTOS-TECH',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => "x3 computador - webcam - teclado - mouse\nMesa - silla\nCámara habitación\nIluminación\nVentilador",
            ],
            [
                'name' => 'Cuartos - Arte',
                'code' => 'CTOS-ARTE',
                'manager' => 'Por asignar',
                'monthly_budget' => 0,
                'status' => 'Activo',
                'notes' => null,
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                $department,
            );
        }
    }
}
