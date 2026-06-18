<?php

namespace App\Http\Controllers;

use App\Models\AdminBroadcastMessageRecipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlatformMessageController extends Controller
{
    public function dismiss(Request $request, AdminBroadcastMessageRecipient $recipient): RedirectResponse
    {
        $this->authorize('dismiss', $recipient);

        $recipient->dismiss();

        return redirect()->back();
    }
}
