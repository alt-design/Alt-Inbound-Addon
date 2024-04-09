<?php namespace AltDesign\AltInbound\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Filesystem\Manager;

use Statamic\Fields\BlueprintRepository;
use Statamic\Fields\Blueprint;

use AltDesign\AltInbound\Helpers\Data;

class AltInboundController
{

    public function blacklist(Request $request)
    {
        $state = (int) $request->get('blacklist');
        with(new Data('Inbound'))->setBlacklist($state);
        return;
    }

    public function customView(Request $request)
    {
        $name = $request->get('custom-view');
        with(new Data('Inbound'))->setCustomView($name);
        return;
    }

    public function block(Request $request)
    {
        return view(Data::customView() ?? 'alt-inbound::block');
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

        $blacklist = Data::whitelisting() ? 0 : 1;

        // Reset the directory to the old one
//        Blueprint::setDirectory($oldDirectory);

        return view('alt-inbound::index', [
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values(),
            'meta' => $fields->meta(),
            'data' => $values,
            'blacklist' => $blacklist,
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

        // Add the values to the object
        $arr = $request->all();
        $arr['id'] = uniqid();
        $fields = $fields->addValues($arr);
        $fields->validate();

        $data->setAll($fields->process()->values()->toArray());

        $data = new Data('Inbound');
        $values = $data->all();

        return [
            'data' => $values
        ];
    }

    public function delete(Request $request)
    {
        $manager = new Manager();
        $manager->disk()->delete('content/alt-inbound/blocks/' . base64_encode($request->get('ip')) . '.yaml');

        $data = new Data('Inbound');
        $values = $data->all();
        return [
            'data' => $values
        ];
    }

    public function export(Request $request)
    {
        $data = new Data('Inbound');

        $callback = function() use ($data) {
            $df = fopen("php://output", 'w');

            fputcsv($df, ['id', 'ip']);

            // Use the data from the request instead of fetching from the database
            foreach ($data->data as $row) {
                fputcsv($df, [$row['id'], $row['ip'] ]); // Adjust as per your data structure
            }
            fclose($df);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="blocks-'.date('Y-m-d\_H:i:s').'.csv"',
        ]);
    }
    public function import(Request $request)
    {
        $currentData = json_decode($request->get('data'), true);
        $file = $request->file('file');
        $handle = fopen($file->path(), 'r');
        if ($handle !== FALSE) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== FALSE) {
                $temp = [
                    'id' => $row[0],
                    'ip' => $row[1],
                ];
                foreach ($currentData as $rdKey => $block) {
                    if ($block['id'] === $temp['id'] || $block['ip'] === $temp['ip']) {
                        $currentData[$rdKey] = $temp;
                        continue 2;
                    }
                }
                $currentData[] = $temp;
            }
            // Close the file handle
            fclose($handle);
        }
        $data = new Data('Inbound');
        $data->saveAll($currentData);
        return;
    }
}
