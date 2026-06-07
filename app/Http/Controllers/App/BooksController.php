<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BooksController extends Controller
{
    function index()
    {
        return 'Books';
    }

    function show($id)
    {
        return 'Book ' . $id;
    }

    function store(Request $request)
    {
        return 'Store Book';
    }

    function update(Request $request, $id)
    {
        return 'Update Book ' . $id;
    }

    function destroy($id)
    {
        return 'Delete Book ' . $id;
    }
}
