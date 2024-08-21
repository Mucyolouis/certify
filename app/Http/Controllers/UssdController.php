<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Church;
use Illuminate\Http\Request;
use App\Models\TransferRequest;
use Illuminate\Support\Facades\Hash;

class UssdController extends Controller
{

    public function handleUssd(Request $request)
{
    $text = $request->input('text');
    $textArray = explode('*', $text);
    $level = count($textArray);

    if ($level == 1) {
        return $this->loginPrompt();
    }

    if ($level == 2) {
        return $this->passwordPrompt($textArray[1]);
    }

    $user = $this->authenticateUser($textArray[1], $textArray[2]);
    if (!$user) {
        return "END Invalid credentials. Please try again.";
    }

    switch ($level) {
        case 3:
            return $this->showUserInfo($user);
        case 4:
            return $this->handleMainMenuOption($user, $textArray[3]);
        default:
            return "END Invalid option.";
    }
}


    private function loginPrompt()
    {
        return "CON Welcome to Church USSD Service\nPlease enter your email:";
    }

    private function passwordPrompt($email)
    {
        return "CON Please enter your password:";
    }

    private function authenticateUser($email, $password)
    {
        $user = User::where('email', $email)->first();
        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }
        return null;
    }

    private function showUserInfo($user)
    {
        $baptismStatus = $user->baptized ? "Baptized" : "Not baptized";
        $churchInfo = $user->church ? $user->church->name : "Not assigned";

        return "CON Your Information:\n" .
            "Marital status: {$user->marital_status}\n" .
            "Baptism status: {$baptismStatus}\n" .
            "Church: {$churchInfo}\n" .
            "\nMain Menu:\n" .
            "1. Transfer Request\n" .
            "2. Logout";
    }


    private function transferRequest($user, $input = null)
    {
        // Check for existing pending transfer requests
        // $pendingRequest = TransferRequest::where('user_id', $user->id)
        //     ->where('status', 'pending')
        //     ->first();

        // if ($pendingRequest) {
        //     return "END You already have a pending transfer request. Please wait for it to be processed.";
        // }

        // If no input, start the transfer request process
        if ($input === null) {
            return "CON Enter the name of the church you wish to transfer to:";
        }

        $inputParts = explode('*', $input);
        $step = count($inputParts);

        switch ($step) {
            case 1:
                // User has entered the church name, now ask for reason
                $churchName = $inputParts[0];
                return "CON Enter the reason for your transfer request:";
            case 2:
                // User has entered both church name and reason, save the request
                $churchName = $inputParts[0];
                $reason = $inputParts[1];

                // Find or create the church
                $church = Church::firstOrCreate(['name' => $churchName]);

                // Create the transfer request
                TransferRequest::create([
                    'christian_id' => $user->id,
                    'from_church_id' => $user->church_id,
                    'to_church_id' => $church->id,
                    'description' => $reason,
                    'approval_status' => 'pending'
                ]);

                return "END Your transfer request has been submitted successfully. You will be notified via email once it's processed.";
            default:
                return "END Invalid input. Please try again.";
        }
    }



    private function mainMenu()
    {
        return "CON Main Menu\n1. Check Marital Status\n2. Check Baptism Status\n3. Check Church Information\n4. Check Personal Information\n5. Logout\n6. Exit";
    }



    private function handleMainMenuOption($user, $option)
    {
        switch ($option) {
            case '1':
                return $this->transferRequest($user);
            case '2':
                return $this->logout();
            default:
                return "END Invalid option selected.";
        }
    }
}