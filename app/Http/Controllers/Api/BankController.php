<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BankResource;
use App\Models\Bank;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BankController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $banks = Bank::active()->orderBy('name')->get();

        return BankResource::collection($banks);
    }
}
