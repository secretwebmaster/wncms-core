<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Plugin;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function index()
    {
        $plugins = Plugin::all();
        return $this->view('backend.plugins.index', [
            'page_title' => wncms_model_word('plugin', 'management'),
            'plugins' => $plugins,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'plugin_file' => 'required|mimes:zip',
        ]);

        $file = $request->file('plugin_file');
        $pluginName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $filePath = $file->storeAs('plugins', $pluginName . '.zip');

        // Save metadata in the database
        Plugin::create([
            'name' => $pluginName,
            'description' => 'Description of the plugin',
            'version' => '1.0',
            'path' => $filePath,
        ]);

        return redirect()->route('plugins.index')->with('success', 'Plugin uploaded successfully!');
    }

    public function activate(Plugin $plugin)
    {
        $plugin->update(['status' => 'active']);

        return redirect()->route('plugins.index')->with('success', 'Plugin activated successfully!');
    }

    public function deactivate(Plugin $plugin)
    {
        $plugin->update(['status' => 'inactive']);

        return redirect()->route('plugins.index')->with('success', 'Plugin deactivated successfully!');
    }

    public function delete(Plugin $plugin)
    {
        $plugin->delete();

        return redirect()->route('plugins.index')->with('success', 'Plugin deleted successfully!');
    }

    /**
     * Fetch view
     */
    public function view(string $name, array $options = [])
    {
        if (view()->exists($name)) {
            return view($name, $options);
        }

        $defaultView = 'wncms::' . $name;
        if (view()->exists($defaultView)) {
            return view($defaultView, $options);
        }

        abort(404, "View [{$name}] not found.");
    }
}
