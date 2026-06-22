<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
 
class ImageUploadController extends Controller
{
    /**
     * Handle the image upload.
     *
     * POST /api/admin/upload-image
     */
    public function upload(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user || !$user->isGerantOrAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Only GERANT or ADMIN can upload images.',
            ], 403);
        }
 
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);
 
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }
 
        try {
            $file = $request->file('image');
            $path = $file->store('uploads', 'public');
            
            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully.',
                'url'     => '/storage/' . $path,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to store image.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
