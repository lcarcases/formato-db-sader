<?php

namespace App\Core\Shared\Infrastructure;

use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use App\Core\Shared\Application\DTO\IDto;

class Respuesta {

    private $success;
    private $message;
    private $data;
    private $code;

    function __construct() {

         $this->success = true;
         $this->message = "";
         $this->data = null;
         $this->code = 200;
    }

    public function getSuccess() {
        try {
           return $this->success;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function setSuccess($success) {
        try {
           $this->success = $success;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function getCode() {
        try {
           return $this->code;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function setCode($code) {
        try {
           $this->code = $code;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function getMessage() {
        try {
           return $this->message;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function setMessage($message) {
        try {
           $this->message = $message;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function getData() {
        try {
           return $this->data;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function setData($data) {
        try {
           $this->data = $data;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function successResponse($logMessage = null) {
        try {

            $this->writeLog($logMessage);

            return response()->json([
                'message' => $this->message,
                'success' => $this->success,
                'data'    => $this->data,
                'code'    => $this->code
            ],
            $this->code, 
            [], 
            JSON_UNESCAPED_UNICODE
           );
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function successResponseComponent($logMessage = null) {
        try {

            $this->writeLog($logMessage);

            return[
                'message' => $this->message,
                'success' => $this->success,
                'data'    => $this->data,
                'code'    => $this->code
            ];

        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function successResponseComponentLivewire($logMessage = null) {
        try {

            $this->writeLog($logMessage);

            return [
                'message' => $this->message,
                'success' => $this->success,
                'data'    => $this->arrDtosToLivewire(),
                'code'    => $this->code
            ];

        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function errorResponseComponentLivewire($ex)
    {
        try {

            $this->success = false;
            $this->data = null;

            if (!($ex instanceof Exception)) {
                $this->message = $ex->getMessage();
            }else if ($ex instanceof ValidationException) {
                $this->message = $ex->validator->errors()->getMessages();
                $this->code = 422;
            }else if ($ex instanceof ModelNotFoundException) {
                $modelo = class_basename($ex->getModel());
                $this->message = "No existe ninguna instancia para el modelo {$modelo} con los filtros proporcionados";
                $this->code = 404;
            }else if ($ex instanceof AuthenticationException) {
                $this->message = "Usuario no autenticado";
                $this->code = 401;
            }else if ($ex instanceof AuthorizationException) {
                $this->message = "No posee los  permisos para ejecutar esta acción";
                $this->code = 403;
            }else if ($ex instanceof NotFoundHttpException) {
                $this->message = "No se encontró la url o recurso especificado";
                $this->code = 404;
            }else if ($ex instanceof MethodNotAllowedHttpException) {
                $this->message = "El método o verbo especificado en la petición no es válido";
                $this->code = 405;
            }else if ($ex instanceof HttpException) {
                $this->message = $ex->getMessage();
                $this->code = $ex->getStatusCode();
            }else if ($ex instanceof QueryException) {
                $codigo = $ex->errorInfo[1];
                if ($codigo == 1451) {
                    $this->message = "No se puede eliminar de forma permanente el recurso porque está relacionado con algún
                                      otro mediante una restricción de integridad referencial";
                    $this->code = 409;
                }
            }else if ($this->code == 500) {
                $this->writeErrorLog('En el archivo: ' . $ex->getFile() . ', en la línea: ' . $ex->getLine() . ' se presentó el siguiente error: ' . $ex->getMessage() . "\n" . $ex->getTraceAsString());
            }

            return [
                'message' => $this->message,
                'success' => $this->success,
                'data'    => $this->data,
                'code'    => $this->code
            ];
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function errorResponse($ex) {
        try {

            $this->success = false;
            $this->data = null;

            $this->message = "Falla inesperada. Consulte al administrador del sistema";
            $this->code = 500;

            if(!($ex instanceof Exception)) {
                $this->message = $ex->getMessage();
            }

            if($ex instanceof ValidationException) {
                $this->message = $ex->validator->errors()->getMessages();
                $this->code = 422;
            }

            if($ex instanceof ModelNotFoundException) {
                $modelo = class_basename($ex->getModel());
                $this->message = "No existe ninguna instancia para el modelo {$modelo} con los filtros proporcionados";
                $this->code = 404;
            }

            if($ex instanceof AuthenticationException) {
                $this->message = "Usuario no autenticado";
                $this->code = 401;
            }

            if($ex instanceof AuthorizationException) {
                $this->message = "No posee los  permisos para ejecutar esta acción";
                $this->code = 403;
            }

            if($ex instanceof NotFoundHttpException) {
                $this->message = "No se encontró la url o recurso especificado";
                $this->code = 404;
            }

            if($ex instanceof MethodNotAllowedHttpException) {
                $this->message = "El método o verbo especificado en la petición no es válido";
                $this->code = 405;
            }

            if($ex instanceof HttpException) {
                $this->message = $ex->getMessage();
                $this->code = $ex->getStatusCode();
            }

            if($ex instanceof QueryException) {
                $codigo = isset($ex->errorInfo) && isset($ex->errorInfo[1]) ? $ex->errorInfo[1] : null;
                if($codigo == 1451) {
                    $this->message = "No se puede eliminar de forma permanente el recurso porque está relacionado con algún
                                      otro mediante una restricción de integridad referencial";
                    $this->code = 409;
                } else {
                    $this->message = "Se produjo un error durante la ejecución de una consulta a la base de datos: ";
                    $this->code = 500;
                }
            }

            if($this->code == 500) {
                $this->writeErrorLog('En el archivo: '.$ex->getFile().', en la línea: '.$ex->getLine().' se presentó el siguiente error: '.$ex->getMessage()."\n".$ex->getTraceAsString());
            }



            return response()->json([
                                        'message' => $this->message,
                                        'success' => $this->success,
                                        'data'    => $this->data,
                                        'code'    => $this->code
                                    ],
                                    $this->code, 
                                    [], 
                                    JSON_UNESCAPED_UNICODE
                                   );
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function descargarArchivo($responseZipFile) {
        try {
            $fileName = '';
            $headers = [];
            $zipFile = null;
            if($responseZipFile->successful()) {
                $zipFile = $responseZipFile->body();
                $headers = $responseZipFile->headers();
                $contentDisposition = $headers['Content-Disposition'][0];
                if (preg_match('/filename=([^;,]+)/', $contentDisposition, $matches)) {
                    $fileName = $matches[1];
                } else {
                    $fileName = 'archivo.zip';
                }
                $headers = [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                ];
                return response($zipFile, 200, $headers);
            } else {
                $this->message = "No se pudo recuperar el archivo zip";
                $this->code = 500;
                $this->success = false;
                return response()->json([
                                            'message' => $this->message,
                                            'success' => $this->success,
                                            'data'    => $this->data,
                                            'code'    => $this->code
                                        ],
                                        $this->code
                                       );
            }
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function descargarArchivoPdf($file,$path) {
        try {
            return response($file, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . basename($path) . '"',
            ]);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function toJson() {
        try {
             $json = json_encode(get_object_vars($this));
             return $json;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function arrDtosToLivewire() {
        try {
            $arrLivewire = [];
            foreach ($this->data as $dto) {
                if($dto instanceof IDto) {
                    $arrLivewire[] = $dto->toObjectArray();
                }
            }
            return $arrLivewire;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function dtoToLivewire() {
        try {
            if($this->data instanceof IDto) {
                return $this->data->toObjectArray();
            }
            return null;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    private function writeLog($logMessage) {
        if($logMessage) {
            Log::info($logMessage);
        }
    }

    private function writeErrorLog($logMessage) {
        try {
            if($logMessage) {
                 Log::error($logMessage);
             }
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

}
