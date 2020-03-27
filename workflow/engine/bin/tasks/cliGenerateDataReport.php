<?php

use Illuminate\Support\Facades\DB;
use ProcessMaker\Core\System;

CLI::taskName("generate-data-report");
CLI::taskRun("generateDataReport");
CLI::taskDescription("\nGenerate data report by process in a respective workspace, you must pass through arguments the project identifier and the processing interval.");
CLI::taskArg("workspace", false);
CLI::taskOpt("uid", "Identifier that represents the process, must be 32 characters.", "", "process=");
CLI::taskOpt("start", "The start option skips so many rows before returning results.", "", "start=");
CLI::taskOpt("limit", "The limit option restricts the number of rows returned.", "", "limit=");

/**
 * Generate data report by process in a respective workspace, you must pass through 
 * arguments the project identifier and the processing interval.
 * @param array $options
 * @return void
 */
function generateDataReport(array $options): void
{
    //get workspace
    if (empty($options[0])) {
        CLI::logging("Workspace undefined!\n");
        return;
    }
    $workspace = $options[0];

    //get options
    $parameters = [
        "tableName=" => "",
        "type=" => "",
        "process=" => "",
        "gridKey=" => "",
        "additionalTable=" => "",
        "className=" => "",
        "pathWorkspace=" => "",
        "start=" => "",
        "limit=" => ""
    ];
    foreach ($parameters as $key => $value) {
        for ($i = 1; $i < count($options); $i++) {
            if (strpos($options[$i], $key) !== false) {
                $parameters[$key] = str_replace($key, "", $options[$i]);
                break;
            }
        }
    }

    //validations
    $needed = [
        "process="
    ];
    foreach ($needed as $value) {
        if (empty($parameters[$value])) {
            CLI::logging("Missing options {$value}.\n");
            return;
        }
    }

    //run method
    $workspaceTools = new WorkspaceTools($workspace);
    $workspaceTools->generateDataReport(
            $parameters["tableName="],
            $parameters["type="],
            $parameters["process="],
            $parameters["gridKey="],
            $parameters["additionalTable="],
            $parameters["className="],
            $parameters["pathWorkspace="],
            (int) $parameters["start="],
            (int) $parameters["limit="]
    );
}
