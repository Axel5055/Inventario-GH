<?php

use App\Livewire\RegistroEquipoComputo;
use Illuminate\Support\Facades\Route;

Route::get('/', RegistroEquipoComputo::class)->name('registro.equipo-computo');

Route::redirect('/registro-equipo', '/');
