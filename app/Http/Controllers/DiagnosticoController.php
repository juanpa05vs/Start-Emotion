<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DiagnosticoController extends Controller
{
    /**
     * Muestra el menú principal con el catálogo de minijuegos.
     */
    public function index()
    {
        return view('minijuegos.index');
    }

    /**
     * Carga el minijuego de descarte lógico "Adivina Quién".
     */
    public function diagnostico()
    {
        return view('minijuegos.diagnostico');
    }
}
