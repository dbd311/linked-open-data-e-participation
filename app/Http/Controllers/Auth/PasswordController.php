<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ResetsPasswords;
use App\Http\Controllers\WebPageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class PasswordController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Password Reset Controller
      |--------------------------------------------------------------------------
      |
      | This controller is responsible for handling password reset requests
      | and uses a simple trait to include this behavior. You're free to
      | explore this trait and override any methods you wish to tweak.
      |
     */

use ResetsPasswords;

    /**
     * Create a new password controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('guest');
    }

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Http\Response
     */
    public function getEmail(Request $request) {
        WebPageController::updateLocale($request->get('lang'));
        return view('auth.password');
    }

    /**
     * Site base URL
     * @return {String} url
     */
    public function url() {
        return sprintf(
                "%s://%s%s", isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http', $_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']
        );
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postEmail(Request $request) {
        $user = UserController::selectUser($request->get('email'));
        if ($user != null) {
            if ($user->user_name->value == '') {
                return redirect()->back()->withErrors([trans('lodepart.mail-no-exist')]);
            }
            // Generate token and date token
            $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $shuffle = str_shuffle($characters);
            $token = substr($shuffle, 0, 15);
            $now = date("c");
            $dateToken = date("c", strtotime("+15 minute", strtotime($now)));
            UserController::updateToken($user->user_id->value, $token, $dateToken);
            if (env('SEND_MAIL') === true) {
                $lang = Config::get('app.locale');
                $message = "<html><body><a href='" . env('SITE_NAME') . "/password/reset?lang=" . $lang . "&token=" . $token . "'>Reset your password</a></body></html>";
                $headers = "From: Lodepart <OPDL-EPARTICIPATION@publications.europa.eu>";
                $headers = 'MIME-Version: 1.0' . "\r\n";
                $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                mail($request->get('email'), 'Lodepart - reset your password', $message, $headers);
            }
            return redirect()->back()->with('status', trans('lodepart.mail-send-click'));
        } else {
            return redirect()->back()->withErrors([trans('lodepart.mail-no-exist')]);
        }
    }

    /**
     * Display the password reset view for the given token.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getReset(Request $request) {
        if (is_null($request->get('token'))) {
            throw new NotFoundHttpException;
        }

        WebPageController::updateLocale($request->get('lang'));

        $user = UserController::selectUserFromToken($request->get('token'));
        $token = null;
        if ($user != null && date("c") < $user->token_date->value) {
            $token = $request->get('token');
        }

        return view('auth.reset')
                        ->with('token', $token);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resetUserPassword(Request $request) {
        if ($request->get('email') == '' || $request->get('password') == '' || $request->get('password_confirmation') == '') {
            return redirect()->back()
                            ->withInput($request->only('email'))
                            ->withErrors([trans('lodepart.mandatory-id-reset')]);
        }
        if ($request->get('password') != $request->get('password_confirmation')) {
            return redirect()->back()
                            ->withInput($request->only('email'))
                            ->withErrors([trans('lodepart.different-password')]);
        }
        $user = UserController::selectUserFromToken($request->get('token'));
        if ($user != null && $user->mail->value == md5($request->get('email'))) {
            $userURI = UserController::buildUserURI($user->user_id->value);
            UserController::updatePassword($userURI, $request->get('password'));
            Session::put('user', $user);
            return redirect()->intended('/?lang=' . $request->get('lang'));
        } else {
            return redirect()->back()
                            ->withInput($request->only('email'))
                            ->withErrors([trans('lodepart.mail-no-exist')]);
        }
    }

}
