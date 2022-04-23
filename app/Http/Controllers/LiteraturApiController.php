<?php

namespace App\Http\Controllers;

use App\Models\Literatur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LiteraturApiController extends Controller
{
    /**
     * Get all Literatur.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $literatur = Literatur::getAllLiteratur();
        return response()->json([
            'code' => 200,
            'message' => [
                'value' => 'All literatur retrieved successfully.',
            ],
            'literatur' => $literatur,
        ]);
    }

    /**
     * Get one data of Literatur by its id.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $literatur = Literatur::getLiteraturById($id);

        if(!$literatur){
            return response()->json([
                'code' => 404,
                'message' => [
                    'value' => 'Literatur not found.',
                ],
                'literatur' => null,
            ]);
        }

        return response()->json([
            'code' => 200,
            'message' => [
                'value' => 'Literatur retrieved successfully.',
            ],
            'literatur' => $literatur,
        ]);
    }

    /**
     * Store a new Literatur.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if (Auth::user()->role != 'Dosen') {
            return response()->json([
                'code' => 401,
                'message' => [
                    'value' => 'You are not authorized to access this resource.',
                ],
                'literatur' => null,
            ]);
        }

        $validation = Validator::make($request->all(), [
            'judul' => 'required',
            'penulis' => 'required',
            'tahun_terbit' => 'required|numeric|digits:4',
            'tag' => 'required',
            'file' => 'required|mimes:pdf',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'code' => 400,
                'message' => $validation->errors(),
                'literatur' => null,
            ]);
        }

        /* Saving data */
        $literatur = new Literatur();
        $literatur->judul = $request->judul;
        $literatur->penulis = $request->penulis;
        $literatur->tahun_terbit = $request->tahun_terbit;
        $literatur->tag = $request->tag;
        $literatur->id_user = Auth::user()->id;

        /* Saving file to directory */
        $this->extracted($request, $literatur);
        /* End of saving data */

        $literatur->save();

        return response()->json([
            'code' => 201,
            'message' => [
                'value' => 'Literatur created successfully.',
            ],
            'literatur' => $literatur,
        ]);
    }

    /**
     * Update data of existing Literatur.
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(int $id, Request $request)
    {
        if (Auth::user()->role != 'Dosen') {
            return response()->json([
                'code' => 401,
                'message' => [
                    'value' => 'You are not authorized to access this resource.',
                ],
                'literatur' => null,
            ]);
        }

        $validation = Validator::make($request->all(), [
            'judul' => 'required',
            'penulis' => 'required',
            'tahun_terbit' => 'required|numeric|digits:4',
            'tag' => 'required',
            'file' => 'mimes:pdf',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'code' => 400,
                'message' => $validation->errors(),
                'literatur' => null,
            ]);
        }

        $literatur = Literatur::find($id);

        if(!$literatur) {
            return response()->json([
                'code' => 404,
                'message' => [
                    'value' => 'Literatur not found.',
                ],
                'literatur' => null,
            ]);
        }

        $literatur->judul = $request->judul;
        $literatur->penulis = $request->penulis;
        $literatur->tahun_terbit = $request->tahun_terbit;
        $literatur->tag = $request->tag;

        // If user sends a new file, then the old file will be deleted and replaced with the new one
        if ($request->file('file')) {
            // Delete old file
            unlink($literatur->file);

            // Save new file
            // A thing that is known regards to updating file
            // It seems like that Laravel actually checks whether the file is the same or not
            // If it turns out to be the same file - even with different name - then it will not update the file
            $this->extracted($request, $literatur);
        }

        $literatur->save();

        return response()->json([
            'code' => 200,
            'message' => [
                'value' => 'Literatur updated successfully.',
            ],
            'literatur' => $literatur,
        ]);
    }

    /**
     * Delete data of existing Literatur permanently.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        if (Auth::user()->role != 'Dosen') {
            return response()->json([
                'code' => 401,
                'message' => [
                    'value' => 'You are not authorized to access this resource.',
                ],
                'literatur' => null,
            ]);
        }

        $literatur = Literatur::find($id);

        if (!$literatur) {
            return response()->json([
                'code' => 404,
                'message' => [
                    'value' => 'Literatur not found.',
                ],
                'literatur' => null,
            ]);
        }

        unlink($literatur->file);
        $literatur->delete();

        return response()->json([
            'code' => 200,
            'message' => [
                'value' => 'Literatur deleted successfully.',
            ],
            'literatur' => null,
        ]);
    }

    /**
     * This function is used to extract the file from the request and save it to a directory
     * Directory is /public/uploaded/literatur/{name_of_file}
     *
     * @param Request $request
     * @param $literatur
     * @return void
     */
    public function extracted(Request $request, $literatur): void
    {
        $file = $request->file('file');
        $fileExtension = $file->getClientOriginalExtension();
        $judulWithoutSpace = preg_replace('/\s+/', '', $request->judul);
        $fileName = $judulWithoutSpace . '-' . date("d-m-Y_H-i-s") . '.' . $fileExtension;
        $file->move(Literatur::$FILE_DESTINATION, $fileName);

        $literatur->file = Literatur::$FILE_DESTINATION . '/' . $fileName;
    }
}
