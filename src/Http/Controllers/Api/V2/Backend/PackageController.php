<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Wncms\Models\Package;

class PackageController extends ApiV2Controller
{
    public function index(Request $request)
    {
        try {
            $registered = collect(wncms()->getRegisteredPackages());
            $installed = Package::query()->get()->keyBy('package_id');

            $rows = $registered->map(function ($item, $key) use ($installed) {
                $id = is_string($key) ? $key : (string) data_get($item, 'package_id', '');
                $name = (string) data_get($item, 'name', $id);
                $version = (string) data_get($item, 'version', '');
                $description = (string) data_get($item, 'description', '');
                $installedRow = $installed->get($id);

                return [
                    'package_id' => $id,
                    'name' => $name,
                    'version' => $version,
                    'description' => $description,
                    'status' => $installedRow?->status ?? 'inactive',
                    'installed_at' => $installedRow?->created_at,
                    'updated_at' => $installedRow?->updated_at,
                ];
            })->values();

            return $this->ok($rows);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }
}
