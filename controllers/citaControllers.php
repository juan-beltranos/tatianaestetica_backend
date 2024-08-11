<?php

namespace Controllers;

use Model\Citas;
use Model\CitaServicio;
use Model\Login;

class citaControllers
{

    public static function getCitas()
    {
        $citas = Citas::all();
        echo json_encode($citas);
    }

    public static function getCitasId($id)
    {
        $cita = Citas::where('id', $id);
        if (!$cita) {
            $respuesta = [
                'tipo' => 'error',
                'mensaje' => 'No existe esa cita'
            ];
            echo json_encode($respuesta);
            return;
        }
        echo json_encode($cita);
    }

    public static function postCitas()
    {
        $citas = new Citas;

        if ($_SERVER['REQUEST_METHOD'] === "POST") {

            $citas->sincronizar($_POST);

            $fecha = $citas->fecha;
            $hora = $citas->hora;

            // Validar campos vacíos
            if (!$fecha || !$hora) {
                http_response_code(400);
                $res = [
                    'tipo' => 'error',
                    'msg' => 'Ningún campo puede ir vacío'
                ];
                echo json_encode(['respuesta' => $respuesta]);
                return;
            }

            // Validar si existe ese usuario por ID
            $usuarioId = Login::where('id', $citas->usuarioId);
            if (!$usuarioId) {
                http_response_code(400);
                $respuesta = [
                    'tipo' => 'error',
                    'mensaje' => 'Ese usuario no existe',
                ];
                echo json_encode(['respuesta' => $respuesta]);
                return;
            }

            // Verificar si ya existe una cita programada en la misma fecha y hora
            $consulta = "SELECT id FROM citas WHERE fecha = '$fecha' AND hora = '$hora'";
            $citaExistente = Citas::SQL($consulta);

            if ($citaExistente) {
                http_response_code(400);
                $respuesta = [
                    'tipo' => 'error',
                    'mensaje' => 'Ya existe una cita programada para esa fecha y hora, porfavor seleccione otra fecha u hora.',
                ];
                echo json_encode(['respuesta' => $respuesta]);
                return;
            }

            // TODO: Validar si el servicio es Keratina en la fecha seleccionada (no se pueden agendar más citas ya que la Keratina ocupa todo el día).

            // Crear e insertar la cita en la BD
            $save = $citas->crear();
            $id = $save['id'];

            // Almacena los servicios con el Id de la cita
            $idServicios = explode(",", $_POST['servicios']);
            foreach ($idServicios as $idServicio) {
                $args = [
                    'citaId' => $id,
                    'servicioId' => $idServicio
                ];
                $citaServicio = new CitaServicio($args);
                $citaServicio->guardar();
            }

            // Almacena la cita y el servicio
            $resultado = [
                'cita' => $citas,
                'servicios' => $idServicios
            ];

            if ($resultado) {
                $respuesta = [
                    'tipo' => 'exito',
                    'mensaje' => 'Cita creada correctamente',
                ];
                echo json_encode(['respuesta' => $respuesta]);
            }

        }
    }

    public static function putCitas($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar la cita exista
            $cita = Citas::where('id', $id);

            if (!$cita) {
                $respuesta = [
                    'tipo' => 'error',
                    'mensaje' => 'Esa cita no existe'
                ];
                echo json_encode($respuesta);
                return;
            }

            // Verificar si ya existe una cita programada en la misma fecha y hora
            $consulta = "SELECT id FROM citas WHERE fecha = '$fecha' AND hora = '$hora'";
            $citaExistente = Citas::SQL($consulta);

            if ($citaExistente) {
                http_response_code(400);
                $respuesta = [
                    'tipo' => 'error',
                    'mensaje' => 'Ya existe una cita programada para esa fecha y hora, porfavor seleccione otra fecha u hora.',
                ];
                echo json_encode($respuesta);
                return;
            }

            $citaPut = new Citas($_POST);
            $citaPut->id = $cita->id;
            $resultado = $citaPut->actualizar();

            if ($resultado) {
                $respuesta = [
                    'tipo' => 'exito',
                    'id' => $citaPut->id,
                    'mensaje' => 'Actualizado correctamente'
                ];
                echo json_encode(['respuesta' => $respuesta]);
            }
        }
    }

    public static function deleteCita($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Validar que el servicio exista
            $servicio = Citas::where('id', $id);

            if (!$servicio) {
                http_response_code(400);
                $respuesta = [
                    'tipo' => 'error',
                    'mensaje' => 'Esa cita no existe :('
                ];
                echo json_encode($respuesta);
                return;
            }

            $servicioDelete = new Citas($_POST);
            $servicioDelete->id = $servicio->id;

            $resultado = $servicioDelete->eliminar();

            http_response_code(200);
            $resultado = [
                'resultado' => $resultado,
                'mensaje' => 'Cita cancelada Correctamente',
                'tipo' => 'exito'
            ];

            echo json_encode($resultado);
        }
    }


}
