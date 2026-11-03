<?php namespace AltDesign\AltInbound\Http\Controllers;

use AltDesign\AltInbound\Helpers\BlockCsv;
use AltDesign\AltInbound\Helpers\BlockedView;
use AltDesign\AltInbound\Helpers\Data;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Statamic\Facades\User;
use Statamic\Fields\BlueprintRepository;
use Throwable;

class AltInboundController
{

    public function blacklist(Request $request)
    {
        $request->validate(['blacklist' => ['required', 'boolean']]);

        $blacklisting = $request->boolean('blacklist');
        with(new Data('Inbound'))->setBlacklist($blacklisting);

        $this->logChange('mode changed', ['mode' => $blacklisting ? 'block list' : 'allow list']);
    }

    public function customView(Request $request)
    {
        $request->validate([
            'custom-view' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) {
                if (! BlockedView::isAllowed($value)) {
                    $fail('The view must be one of this site\'s own views and must exist, for example templates.blocked.');
                }
            }],
        ]);

        $name = $request->filled('custom-view') ? $request->input('custom-view') : null;
        with(new Data('Inbound'))->setCustomView($name);

        $this->logChange('blocked page view changed', ['view' => $name]);
    }

    public function block(Request $request)
    {
        $name = Data::customView();

        if (BlockedView::isAllowed($name)) {
            try {
                return response(view($name)->render());
            } catch (Throwable $e) {
                report($e);
            }
        }

        return view(BlockedView::DEFAULT);
    }

    public function index()
    {
        // Grab the old directory just in case
//        $oldDirectory = Blueprint::directory();

        //Publish form
        // Get an array of values
        $data = new Data('Inbound');
        $values = $data->all();

        // Get a blueprint.So
        $blueprint = with(new BlueprintRepository)->setDirectory(__DIR__ . '/../../../resources/blueprints')->find('blocker');
        // Get a Fields object
        $fields = $blueprint->fields();
        // Add the values to the object
        $fields = $fields->addValues($values);
        // Pre-process the values.
        $fields = $fields->preProcess();

        $blacklist = Data::whitelisting() ? false : true;

        // Reset the directory to the old one
//        Blueprint::setDirectory($oldDirectory);

        return Inertia::render('alt-inbound::Index', [
            'blueprint' => $blueprint->toPublishArray(),
            'initialValues' => $fields->values()->all(),
            'initialMeta' => $fields->meta()->all(),
            'items' => $values,
            'blacklist' => $blacklist,
            'customView' => Data::customView(),
        ]);
    }

    public function create(Request $request)
    {

        // Grab the old directory just in case
//        $oldDirectory = Blueprint::directory();

        $data = new Data('Inbound');

        // Get a blueprint.
        $blueprint = with(new BlueprintRepository)->setDirectory(__DIR__ . '/../../../resources/blueprints')->find('blocker');

        // Get a Fields object
        $fields = $blueprint->fields();

        $fields = $fields->addValues(['ip' => trim((string) $request->input('ip'))]);
        $fields->validate();

        $entry = $data->add($fields->process()->values()->get('ip'));

        if ($entry !== null) {
            $this->logChange('block added', ['entry' => $entry['id']]);
        }

        $data = new Data('Inbound');
        $values = $data->all();

        return [
            'data' => $values
        ];
    }

    public function delete(Request $request)
    {
        $request->validate(['id' => ['nullable', 'string'], 'ip' => ['nullable', 'string']]);

        $entry = (new Data('Inbound'))->remove($request->input('id'), $request->input('ip'));

        if ($entry !== null) {
            $this->logChange('block removed', ['entry' => $entry['id']]);
        }

        $data = new Data('Inbound');
        $values = $data->all();

        return redirect()->back()->with([
            'items' => $values,
        ]);
    }

    public function export(Request $request)
    {
        $data = new Data('Inbound');

        $callback = function() use ($data) {
            $df = fopen("php://output", 'w');

            fputcsv($df, BlockCsv::HEADERS, escape: '');

            foreach ($data->all() as $row) {
                fputcsv($df, [BlockCsv::cell($row['id']), BlockCsv::cell($row['ip'])], escape: '');
            }
            fclose($df);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="blocks-'.date('Y-m-d_H-i-s').'.csv"',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:1024']]);

        $ips = BlockCsv::read($request->file('file')->getRealPath());

        $data = new Data('Inbound');
        $added = count(array_filter($ips, fn ($ip) => $data->add($ip) !== null));

        $this->logChange('blocks imported', ['added' => $added, 'already_listed' => count($ips) - $added]);

        $data = new Data('Inbound');

        return redirect()->back()->with([
            'items' => $data->all(),
        ]);
    }

    /**
     * Record a change to the list or its settings, with the user who made it. IP addresses are left out, as personal data.
     *
     * @param  array<string, mixed>  $context
     */
    private function logChange(string $change, array $context = []): void
    {
        Log::info("Alt Inbound: {$change}", ['user' => User::current()?->id(), ...$context]);
    }
}
