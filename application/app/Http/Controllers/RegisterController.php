<?php

namespace App\Http\Controllers;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPMailer;

class RegisterController extends Controller
{

    /**
     * Return the register form view
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function get()
    {
        return view('register');
    }

    /**
     * Check if the user id is not active and active it
     * @param string $code
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function activate($code)
    {
        $user = User::all()->where('id', $code)->first();

        if (!$user)
            return Response('Error', 404);

        if ($user->is_active)
            return view('already_activated');

        $user->is_active = true;
        $user->save();
        return view('activated');
    }

    /**
     * Check register form, create user and send mail if is valid. Show errors otherwise.
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function post(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|unique:authentication_user|min:4|max:255',
            'email' => 'required|email|unique:authentication_user',
            'password' => 'required|min:6|max:255|confirmed',
            'g-recaptcha-response' => 'required|recaptcha',
        ]);

        $id = User::generateId();
        $user = new User([
            'id' => $id,
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'first_name' => '',
            'last_name' => '',
            'is_active' => false,
            'is_staff' => false,
            'is_superuser' => false,
            'space' => 0,
            'date_joined' => Carbon::now(),
        ]);

        if (!$user->save()) {
            return view('register')
                ->withErrors(['There was a problem creating the user. Try again later.']);
        }

        $user->sendActivationMail($id);

        return view('success');
    }
}
