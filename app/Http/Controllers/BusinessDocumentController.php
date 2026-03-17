<?php

namespace App\Http\Controllers;

use App\Models\BusinessDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BusinessDocumentController extends Controller
{
    public function index()
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json([
                'message' => 'Business not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $documents = $business->businessDocuments()->latest()->get();

        return response()->json([
            'message' => 'Business documents fetched successfully',
            'business_documents' => $documents,
        ]);
    }

    public function store(Request $request)
    {
        $business = Auth::user()?->business;

        if (! $business) {
            return response()->json([
                'message' => 'Business not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:registration_certificate,tax_id,utility_bill'],
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $path = $validated['file']->store("business-documents/{$business->id}", 'public');

        $document = $business->businessDocuments()->create([
            'type' => $validated['type'],
            'file_path' => $path,
        ]);

        return response()->json([
            'message' => 'Business document uploaded successfully',
            'business_document' => $document,
        ], Response::HTTP_CREATED);
    }

    public function show(BusinessDocument $businessDocument)
    {
        $this->authorizeDocument($businessDocument);

        return response()->json([
            'message' => 'Business document fetched successfully',
            'business_document' => $businessDocument,
        ]);
    }

    public function update(Request $request, BusinessDocument $businessDocument)
    {
        $this->authorizeDocument($businessDocument);

        $validated = $request->validate([
            'type' => ['sometimes', 'in:registration_certificate,tax_id,utility_bill'],
            'file' => ['sometimes', 'file', 'max:5120'],
        ]);

        if (isset($validated['file'])) {
            Storage::disk('public')->delete($businessDocument->file_path);
            $validated['file_path'] = $validated['file']->store("business-documents/{$businessDocument->business_id}", 'public');
            unset($validated['file']);
        }

        unset($validated['verified_at']);

        $businessDocument->update($validated);

        return response()->json([
            'message' => 'Business document updated successfully',
            'business_document' => $businessDocument->fresh(),
        ]);
    }

    public function destroy(BusinessDocument $businessDocument)
    {
        $this->authorizeDocument($businessDocument);

        Storage::disk('public')->delete($businessDocument->file_path);
        $businessDocument->delete();

        return response()->json([
            'message' => 'Business document deleted successfully',
        ]);
    }

    protected function authorizeDocument(BusinessDocument $businessDocument): void
    {
        $business = Auth::user()?->business;

        abort_unless(
            $business && $businessDocument->business_id === $business->id,
            Response::HTTP_FORBIDDEN
        );
    }
}
