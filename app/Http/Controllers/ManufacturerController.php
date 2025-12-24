<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\StoreManufacturer;
use App\Models\BaseModel;
use App\Models\Manufacturer;

class ManufacturerController extends AccountBaseController
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->manufacturers = Manufacturer::all();
        return view('products.manufacturer.create', $this->data);
    }

    /**
     * @param StoreManufacturer $request
     * @return array
     */
    public function store(StoreManufacturer $request)
    {
        $manufacturer = new Manufacturer();
        $manufacturer->name = $request->name;
        $manufacturer->description = $request->description ?? null;
        $manufacturer->contact_person = $request->contact_person ?? null;
        $manufacturer->email = $request->email ?? null;
        $manufacturer->phone = $request->phone ?? null;
        $manufacturer->address = $request->address ?? null;
        $manufacturer->added_by = user()->id;
        $manufacturer->save();

        $manufacturers = Manufacturer::get();
        $options = BaseModel::options($manufacturers, $manufacturer, 'name');

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $options]);
    }

    /**
     * @param StoreManufacturer $request
     * @param int $id
     * @return array
     */
    public function update(StoreManufacturer $request, $id)
    {
        $manufacturer = Manufacturer::findOrFail($id);
        $manufacturer->name = strip_tags($request->name);
        $manufacturer->description = $request->description ?? null;
        $manufacturer->contact_person = $request->contact_person ?? null;
        $manufacturer->email = $request->email ?? null;
        $manufacturer->phone = $request->phone ?? null;
        $manufacturer->address = $request->address ?? null;
        $manufacturer->last_updated_by = user()->id;
        $manufacturer->save();

        $manufacturers = Manufacturer::get();
        $options = BaseModel::options($manufacturers, null, 'name');

        return Reply::successWithData(__('messages.updateSuccess'), ['data' => $options]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Manufacturer::destroy($id);
        $manufacturerData = Manufacturer::all();
        return Reply::successWithData(__('messages.deleteSuccess'), ['data' => $manufacturerData]);
    }
}
