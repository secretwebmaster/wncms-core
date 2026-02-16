<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Services\Installer\DatabaseManager;
use Wncms\Services\Installer\PermissionChecker;
use Wncms\Services\Installer\RequirementChecker;
use Wncms\Services\Installer\InstallerManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Validator;

class InstallController extends Controller
{
    protected $requirementsChecker;
    protected $permissionChecker;
    protected $databaseManager;
    protected $installer;

    public function __construct()
    {
        $this->installer = new InstallerManager;
        $this->requirementsChecker = new RequirementChecker;
        $this->permissionChecker = new PermissionChecker;
        $this->databaseManager = new DatabaseManager;
    }

    /**
     * Step 1
     * Welcome page
     */
    public function welcome()
    {
        return view('wncms::install.welcome');
    }

    /**
     * Step 2
     * Check requirements
     */
    public function requirements()
    {
        $phpSupportInfo = $this->requirementsChecker->checkPHPversion(config('installer.core.minPhpVersion'));
        $requirements = $this->requirementsChecker->check(config('installer.requirements'));
        return view('wncms::install.requirements', compact('requirements', 'phpSupportInfo'));
    }

    /**
     * Step 3
     * Check permisions
     */
    public function permissions()
    {
        $permissions = $this->permissionChecker->check(config('installer.permissions'));
        return view('wncms::install.permissions', compact('permissions'));
    }

    /**
     * Step 4
     * Confirm installation
     * Then call install() when confirmed
     */
    public function wizard()
    {
        $languages = config('laravellocalization.supportedLocales');
        return view('wncms::install.wizard', [
            'languages' => $languages,
        ]);
    }

    /**
     * ! Start installation
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function install(Request $request)
    {
        parse_str($request->formData, $input);

        $input = $this->installer->normalizeInput($input);

        // validate env rules
        $rules = config('installer.environment.form.rules');
        $messages = [
            'environment_custom.required_if' => __('wncms::installer.environment.wizard.form.name_required'),
            'database_name.required' => __('wncms::word.database_name') . ' ' . __('wncms::word.required'),
            'database_username.required' => __('wncms::word.database_user') . ' ' . __('wncms::word.required'),
            'database_password.required' => __('wncms::word.database_password') . ' ' . __('wncms::word.required'),
        ];
        $validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors(),
            ]);
        }

        $result = $this->installer->runInstallation($input);

        if (!$result['passed']) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::installer.environment.wizard.form.db_connection_failed'),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.started_installation'),
            'reload' => true,
        ]);
    }

    /**
     * Migrate and seed the database.
     *
     * @return \Illuminate\View\View
     */
    public function database()
    {
        $response = $this->databaseManager->migrateAndSeed();
        return redirect()->route('installer.final')->with(['message' => $response]);
    }

    /**
     * Check if installation is completed
     * @return \Illuminate\Http\Response
     */
    public function progress()
    {
        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_fetch'),
            'completed' => wncms_is_installed(),
        ]);
    }

    /**
     * Show when user try to install at installed status 
     */
    public function installed()
    {
        return view('wncms::errors.installed');
    }
}
