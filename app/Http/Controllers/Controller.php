<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * The authenticated member for the current request.
     *
     * Every action that calls this runs behind the `auth` middleware, which
     * redirects unauthenticated requests before they reach the controller.
     * The 401 guard records that invariant rather than handling a reachable case.
     */
    protected function member(Request $request): Member
    {
        $member = $request->user();

        abort_if($member === null, 401);

        return $member;
    }
}
