<?php

// app/Http/Controllers/VoiceDataController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VoiceData;
use Illuminate\Support\Facades\Validator;
use speech_recognition as sr;
use Illuminate\Support\Str;

class VoiceDataController extends Controller
{
    public function recognizeAndStore(Request $request)
    {
//        // Validate the request
//        $validator = Validator::make($request->all(), [
//            'audio' => 'required|mimetypes:audio/mpeg,audio/wav',
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json(['error' => $validator->errors()], 400);
//        }

        // Store the audio file in the temp directory with a random name
        $audio = $request->file('audio');
        $tempFileName = $this->generateRandomFileName($audio->getClientOriginalExtension());
        $audio->move(public_path('python_dir/temp'), $tempFileName);

        // Execute the voice recognition code
        $pythonScript = public_path('python_dir/voice_nlp.py');
        $wavFilePath = public_path('python_dir/temp/'.$tempFileName);
        $command = "python {$pythonScript} {$wavFilePath}";
        $output = shell_exec($command);

        // Retrieve the recognized text from the output
        $recognizedText = trim($output);

        // Store the recognized text in the database
        $voiceData = new VoiceData();
        $voiceData->text = $recognizedText;
        $voiceData->save();

        return response()->json(['text' => $recognizedText], 200);
    }

    /**
     * Generate a random file name.
     *
     * @param string $extension
     * @return string
     */
    private function generateRandomFileName($extension)
    {
        $randomName = Str::random(16);
        return "{$randomName}.{$extension}";
    }

}
