<?php
// This file is part of SunCAE.
// SunCAE is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
// SunCAE is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

chdir("../data/{$owner}/cases/{$id}/");
$fee = file("case.fee", FILE_IGNORE_NEW_LINES);
$fee_text = implode("\n", $fee);
$response["header"] = isset($fee[0]) ? htmlspecialchars($fee[0]) . "<br>" : "";
$response["plain"] = implode("\n", array_slice($fee, 1));

$response["html"] = "<pre><code>" . htmlspecialchars($fee_text) . "</code></pre>";
$pandoc = realpath(__DIR__ . "/../../bin/pandoc");
$syntax = realpath(__DIR__ . "/feenox.xml");
if ($pandoc !== false && $syntax !== false && is_executable($pandoc)) {
	$command = [$pandoc, "-t", "html", "--syntax-definition={$syntax}"];
	$descriptor_spec = [
		0 => ["pipe", "r"],
		1 => ["pipe", "w"],
		2 => ["pipe", "w"],
	];
	$process = proc_open($command, $descriptor_spec, $pipes, null, null);
	if (is_resource($process)) {
		fwrite($pipes[0], "~~~feenox\n{$fee_text}\n~~~\n");
		fclose($pipes[0]);

		$highlighted_html = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		if (proc_close($process) === 0 && $highlighted_html !== false && $highlighted_html !== "") {
			$response["html"] = $highlighted_html;
		}
	}
}

return_back_json($response);
