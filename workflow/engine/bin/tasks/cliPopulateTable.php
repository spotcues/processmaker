<?php

CLI::taskName("populate-table");
CLI::taskRun("populateTable");
CLI::taskDescription("\nThis function populates the report table with the APP_DATA data");
CLI::taskArg("workspace", false);

/**
 * This function populates the report table with the APP_DATA data.
 * @return void
 */
function populateTable($options): void
{
    //get options
    $workspaceName = $options[0];
    $query = base64_decode($options[1]);
    $isRbac = (bool) $options[2];

    $workspace = new WorkspaceTools($workspaceName);
    $workspace->populateTableReport($query, $isRbac);
}
