<?php

namespace App\Http\Controllers;

use App\Models\TestItem;
use Illuminate\Http\Request;

// for dev only
class DevController extends Controller
{
    // health check
    public function checkHealth()
    {
        return response()->json("ok");
    }

    public function getAllItems()
    {
        $items = TestItem::all();
        return response()->json($items);
    }

    public function getItem($id)
    {
        $item = TestItem::find($id);
        return response()->json($item);
    }

    public function createItem(Request $request)
    {
        $item = TestItem::create($request->all());
        return response()->json($item, 201);
    }

    public function updateItem(Request $request, $id)
    {
        $item = TestItem::find($id);
        $item->update($request->all());
        return response()->json($item, 200);
    }

    public function deleteItem($id)
    {
        TestItem::destroy($id);
        return response()->json(null, 204);
    }
}
