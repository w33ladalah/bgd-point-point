<?php

namespace App\Console\Commands;

use App\Model\Project\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AlterTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:alter-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projects = Project::where('is_generated', true)->get();
        foreach ($projects as $project) {
            $db = env('DB_DATABASE').'_'.strtolower($project->code);
            $this->line('Clone '.$project->code);
            Artisan::call('tenant:database:backup-clone', ['project_code' => strtolower($project->code)]);
            $this->line('Alter '.$project->code);
            config()->set('database.connections.tenant.database', $db);
            DB::connection('tenant')->reconnect();

            DB::connection('tenant')->statement('ALTER TABLE stock_correction_items
            ADD (
                initial_stock decimal(65,30) DEFAULT NULL,
                final_stock decimal(65,30) DEFAULT NULL,
                allocation_id int(10) unsigned DEFAULT NULL
            );');
            DB::connection('tenant')->statement('ALTER TABLE stock_correction_items MODIFY quantity decimal(65,30) signed;');
        }
    }
}
