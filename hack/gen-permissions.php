<?php

$yml = yaml_parse_file(__DIR__ . "/../plugin.yml");

$out = fopen(__DIR__ . "/../gen/permissions.php", "wb");
fwrite($out, "<?php\n");
fwrite($out, "namespace SOFe\\ChoosePlayer;\n");
fwrite($out, "final class Permissions {\n");

foreach($yml["permissions"] ?? [] as $permission => $body) {
    fwrite($out, "    /**\n");
    $descLines = explode("\n", wordwrap($body["description"] ?? ""));
    if($descLines !== [""]) {
        foreach($descLines as $descLine) {
            fwrite($out, "     * $descLine\n");
        }
    }
    fwrite($out, "     */\n");
    $constName = preg_replace('/[^A-Za-z0-9]+/', '_', $permission);
    $constName = preg_replace('/(?<=[a-z])[A-Z]/', '_$0', $constName);
    $constName = strtoupper($constName);
    fwrite($out, "    public const $constName = " . json_encode($permission) . ";\n");
}

fwrite($out, "}");
fclose($out);
