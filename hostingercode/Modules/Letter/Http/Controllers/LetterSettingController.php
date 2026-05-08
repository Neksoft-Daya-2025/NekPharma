<?php

namespace Modules\Letter\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Http\Controllers\AccountBaseController;
use Illuminate\Http\Request;
use Modules\Letter\Entities\LetterSetting;

class LetterSettingController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'letter::app.menu.letter';
    }

    public function index()
    {
        $this->letterSetting = LetterSetting::where('company_id', company()->id)->first();
        
        if (!$this->letterSetting) {
            $this->letterSetting = new LetterSetting();
            $this->letterSetting->company_id = company()->id;
        }

        return view('letter::settings.index', $this->data);
    }

    public function update(Request $request)
    {
        $setting = LetterSetting::where('company_id', company()->id)->first();
        
        if (!$setting) {
            $setting = new LetterSetting();
            $setting->company_id = company()->id;
        }

        try {
            // Prefer new upload over delete: if user removed image (delete=yes) then picks a new file,
            // we must upload; the old if/elseif(delete first) skipped the file entirely.
            if ($request->hasFile('background_image')) {
                $oldImage = $setting->background_image;
                $setting->background_image = Files::uploadLocalOrS3($request->background_image, 'letter-background');

                if ($oldImage) {
                    Files::deleteFile($oldImage, 'letter-background');
                }
            } elseif ($request->input('background_image_delete') === 'yes') {
                if ($setting->background_image) {
                    Files::deleteFile($setting->background_image, 'letter-background');
                }
                $setting->background_image = null;
            }

            $setting->save();
        } catch (\Exception $e) {
            return Reply::error($e->getMessage());
        }

        return Reply::success(__('messages.updateSuccess'));
    }
}

