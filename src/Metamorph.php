<?php

namespace CrucialDigital\Metamorph;

use Countable;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Metamorph
{
    public static function mapFormRequestData(Countable|array $form_data): array
    {
        $form = MetamorphForm::where('_id', $form_data['form_id'] ?? null)
            ->orWhere('entity', $form_data['entity'] ?? '__unknown__')
            ->first();
        if (!$form) {
            return [];
        }

        $extras = Config::models( $form_data['entity'])::extraFields() ?? [];

        $form_inputs = $form['inputs'];

        $rtr = ['form_id' => $form->getAttribute('_id')];
        $rtr['entity'] = $form_data['entity'];

        foreach ($form_data as $k => $datum) {
            $self_input = collect($form_inputs)->firstWhere('field', '=', $k);
            if (!isset($self_input)) {
                if (in_array($k, $extras)) {
                    $rtr[$k] = $datum;
                }
                continue;
            }

            if (in_array($self_input['type'], ['file', 'photo'])) {
                continue;
            }

            if (isset($datum)) {
                if ($self_input['type'] == 'password'
                    && $self_input['encrypted'] == true) {
                    $rtr[$k] = bcrypt($datum);
                } else {
                    $rtr[$k] = $datum;
                }

            }
        }
        return $rtr;
    }

    public static function mapFormRequestFiles(Request $request, string $file_name, string $form_id): array
    {
        $return = [];
        $form = MetamorphForm::find($form_id);
        if (!isset($form)) return $return;
        $form_inputs = $form->inputs()->whereIn('type', ['file', 'photo'])->get();

        foreach ($form_inputs as $input) {
            if (isset($input['field']) && is_string($request->input($input['field']))) {
                $return[$input['field']] = $request->input($input['field']);
                continue;
            }
            if (isset($input['field']) && isset($input['type']) && in_array($input['type'], ['file', 'photo'])) {
                if ($request->hasFile($input['field']) && $request->file($input['field'])->isValid()) {
                    $path = Config::uploadPath() . '/' . $form['entity'] . '/' . now()->format('mY') . '/' . $input['field'] . '/';
                    $file_full_name = $file_name . '.' . $request->file($input['field'])->getClientOriginalExtension();
                    $tmp_name = time() . '_' . $file_full_name;
                    $tmp_full_path = public_path('tmp/' . $tmp_name);
                    $request->file($input['field'])->move(public_path('tmp/'), $tmp_name);
                    if ($input['type'] == 'photo') {
                        Image::make($tmp_full_path)->resize(null, 1000, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })->save($tmp_full_path);
                    }
                    $stored = Storage::put($path . $file_full_name, file_get_contents($tmp_full_path), 'public');
                    unlink($tmp_full_path);
                    if ($stored) {
                        $return[$input['field']] = $path . $file_full_name;
                        if ($input['type'] == 'photo') {
                            try {
                                $cropImage = public_path('/thumbnails');
                                $request->file($input['field'])->move($cropImage);
                                $thumbnail_path = $path . 'thumbnails/thumbnail_' . $file_name;
                                $return[$input['field'] . '_thumbnail'] = static::createThumbnail($cropImage, $thumbnail_path);

                            } catch (\Exception $e) {
                                Log::alert($e->getMessage());
                            }
                        }
                    } else {
                        Log::debug('Can\'t store');
                    }
                } else {
                    Log::debug('No valid file');
                }
            }
        }

        return $return;
    }

    public static function createThumbnail($src_path, $dest_path): ?string
    {
        Image::make($src_path)->resize(null, 300, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->save($src_path);
        $rtr = Storage::put($dest_path, file_get_contents($src_path), 'public');
        unlink($src_path);
        return $rtr ? $dest_path : null;
    }
}
