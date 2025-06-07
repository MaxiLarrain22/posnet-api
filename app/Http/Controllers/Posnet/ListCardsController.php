<?php

namespace App\Http\Controllers\Posnet;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Traits\ApiResponser;

class ListCardsController extends Controller
{
    use ApiResponser;

    public function __invoke()
    {
        $cards = CreditCard::all();
        return $this->successResponse($cards);
    }
}
