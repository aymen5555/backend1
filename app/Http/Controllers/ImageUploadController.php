<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImageUploadController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (! $user || ! $user->isGerantOrAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'image.required' => 'Aucun fichier reçu.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'Seuls les formats JPG, PNG et WEBP sont acceptés.',
            'image.max' => 'Le fichier ne doit pas dépasser 5 Mo.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('image'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $path = $request->file('image')->store('uploads', 'public');

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully.',
                'data' => [
                    'url' => url('/storage/'.$path),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ImageUploadController error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
