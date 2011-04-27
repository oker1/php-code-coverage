<?php

/**
 * PHP_CodeCoverage_Report_Crap4j
 *
 * @author oker <zsolt@takacs.cc>
 */
class PHP_CodeCoverage_Report_Crap4j
{
	private $treshHold = 30;

    /**
     * @param  PHP_CodeCoverage $coverage
     * @param  string           $target
     * @param  string           $name
     * @return string
     */
    public function process(PHP_CodeCoverage $coverage, $target = NULL, $name = NULL)
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = TRUE;

        $root = $document->createElement('crap_result');
        $document->appendChild($root);

        $project = $document->createElement('project', is_string($name) ? $name : '');
        $root->appendChild($project);
        $root->appendChild($document->createElement('timestamp', date('Y-m-d H:i:s', (int)$_SERVER['REQUEST_TIME'])));

		$stats = $document->createElement('stats');
        $methodsNode = $document->createElement('methods');

        $files = $coverage->getSummary();

		$fullMethodCount = 0;
		$fullCrapMethodCount = 0;
		$fullCrapLoad = 0;
		$fullCrap = 0;

        foreach ($files as $filename => $data) {

            if (file_exists($filename)) {

                $file = $document->createElement('file');
                $file->setAttribute('name', $filename);

                $tokens        = PHP_Token_Stream_CachingFactory::get($filename);
                $classesInFile = $tokens->getClasses();
                $linesOfCode   = $tokens->getLinesOfCode();

                $ignoredLines = PHP_CodeCoverage_Util::getLinesToBeIgnored(
                  $filename
                );

                PHP_Token_Stream_CachingFactory::unsetFromCache($filename);
                unset($tokens);

                $lines = array();

                foreach ($classesInFile as $className => $_class) {
                    $package = PHP_CodeCoverage_Util::getPackageInformation(
                      $className, $_class['docblock']
                    );

                    if (!empty($package['namespace'])) {
                        $namespace = $package['namespace'];
                    }

                    $classStatistics = array(
                      'methods'             => 0,
                      'coveredMethods'      => 0,
                      'conditionals'        => 0,
                      'coveredConditionals' => 0,
                      'statements'          => 0,
                      'coveredStatements'   => 0
                    );

                    foreach ($_class['methods'] as $methodName => $method) {

						$methodCount = 0;
						$methodLines = 0;
						$methodLinesCovered = 0;
						$this->methodsDetails($method, $ignoredLines, $files, $filename, $classStatistics, $lines, $methodCount, $methodLines, $methodLinesCovered);

						$fullMethodCount++;

                        $coveragePercent = PHP_CodeCoverage_Util::percent(
                            $methodLinesCovered,
                            $methodLines
                        );
                        $crap = PHP_CodeCoverage_Util::crap($method['ccn'], $coveragePercent);
						$fullCrap += $crap;

						if ($crap >= $this->treshHold) {
							$fullCrapMethodCount++;
						}

						$crapLoad = $this->getCrapLoad($crap, $method['ccn'], $coveragePercent);

						$fullCrapLoad += $crapLoad;

                        $methodNode = $document->createElement('method');

                        $methodNode->appendChild($document->createElement('package', ''));
                        $methodNode->appendChild($document->createElement('className', $className));
                        $methodNode->appendChild($document->createElement('methodName', $methodName));
						$methodNode->appendChild($document->createElement('methodSignature', htmlspecialchars($method['signature'])));
                        $methodNode->appendChild($document->createElement('fullMethod', htmlspecialchars($method['signature'])));
                        $methodNode->appendChild($document->createElement('crap', $this->roundValue($crap)));
                        $methodNode->appendChild($document->createElement('complexity', $method['ccn']));
                        $methodNode->appendChild($document->createElement('coverage', $this->roundValue($coveragePercent)));
                        $methodNode->appendChild($document->createElement('crapLoad', round($crapLoad)));

                        $methodsNode->appendChild($methodNode);

                        $classStatistics['methods']++;

                        if ($methodCount > 0) {
                            $classStatistics['coveredMethods']++;
                        }
                    }
                }
            }
        }

        $stats->appendChild($document->createElement('name', 'Method Crap Stats'));

		$stats->appendChild($document->createElement('methodCount', $fullMethodCount));
		$stats->appendChild($document->createElement('crapMethodCount', $fullCrapMethodCount));
        $stats->appendChild($document->createElement('crapLoad', round($fullCrapLoad)));
		$stats->appendChild($document->createElement('totalCrap', $fullCrap));
		$stats->appendChild($document->createElement('crapMethodPercent', $this->roundValue(100 * $fullCrapMethodCount / $fullMethodCount)));

        $root->appendChild($stats);
		$root->appendChild($methodsNode);

        if ($target !== NULL) {
            if (!is_dir(dirname($target))) {
              mkdir(dirname($target), 0777, TRUE);
            }

            return $document->save($target);
        } else {
            return $document->saveXML();
        }
    }

	private function methodsDetails($method, $ignoredLines, $files, $filename, $classStatistics, $lines, &$methodCount, &$methodLines, &$methodLinesCovered)
	{
		for ($i = $method['startLine'];
			$i <= $method['endLine'];
			$i++) {
			if (isset($ignoredLines[$i])) {
				continue;
			}

			$add = TRUE;
			$count = 0;

			if (isset($files[$filename][$i])) {
				if ($files[$filename][$i] != -2) {
					$classStatistics['statements']++;
					$methodLines++;
				}

				if (is_array($files[$filename][$i])) {
					$classStatistics['coveredStatements']++;
					$methodLinesCovered++;
					$count = count($files[$filename][$i]);
				}

				else if ($files[$filename][$i] == -2) {
					$add = FALSE;
				}
			} else {
				$add = FALSE;
			}

			$methodCount = max($methodCount, $count);

			if ($add) {
				$lines[$i] = array(
					'count' => $count,
					'type' => 'stmt'
				);
			}
		}
	}

	public function getCrapLoad($crapValue, $cyclomaticComplexity, $coveragePercent)
	{
    	$crapLoad = 0;
    	if ($crapValue > $this->treshHold) {
      		$crapLoad += $cyclomaticComplexity * (1.0 - $coveragePercent / 100);
      		$crapLoad += $cyclomaticComplexity / $this->treshHold;
		}
    	return $crapLoad;
	}

    private function roundValue($value)
    {
        return round($value, 2);
    }
}