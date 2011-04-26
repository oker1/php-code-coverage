<?php

/**
 * PHP_CodeCoverage_Report_Crap4j
 *
 * @author oker <zsolt@takacs.cc>
 */
class PHP_CodeCoverage_Report_Crap4j
{
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

        $methodsNode = $document->createElement('methods');

        $files    = $coverage->getSummary();
        $packages = array();

        $projectStatistics = array(
          'files'               => 0,
          'loc'                 => 0,
          'ncloc'               => 0,
          'classes'             => 0,
          'methods'             => 0,
          'coveredMethods'      => 0,
          'conditionals'        => 0,
          'coveredConditionals' => 0,
          'statements'          => 0,
          'coveredStatements'   => 0
        );


        /**
         * <methods>
    <method>
      <package>
        bancoagitar
      </package>
      <className>
        BancoAgitar
      </className>
      <methodName>
        &lt;init&gt;
      </methodName>
      <methodSignature>
        ()V
      </methodSignature>
      <fullMethod>
        public  void &lt;init&gt;()
      </fullMethod>
      <crap>
        1.00
      </crap>
      <complexity>
        1
      </complexity>
      <coverage>
        100.00
      </coverage>
      <crapLoad>
        0
      </crapLoad>
    </method>
         */

        foreach ($files as $filename => $data) {

            $namespace = 'global';

            if (file_exists($filename)) {
                $fileStatistics = array(
                  'classes'             => 0,
                  'methods'             => 0,
                  'coveredMethods'      => 0,
                  'conditionals'        => 0,
                  'coveredConditionals' => 0,
                  'statements'          => 0,
                  'coveredStatements'   => 0
                );

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


                        $methodCount        = 0;
                        $methodLines        = 0;
                        $methodLinesCovered = 0;

                        for ($i  = $method['startLine'];
                             $i <= $method['endLine'];
                             $i++) {
                            if (isset($ignoredLines[$i])) {
                                continue;
                            }

                            $add   = TRUE;
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
                                  'type'  => 'stmt'
                                );
                            }
                        }


                        $coveragePercent = PHP_CodeCoverage_Util::percent(
                            $methodLinesCovered,
                            $methodLines
                        );
                        $crap = PHP_CodeCoverage_Util::crap($method['ccn'], $coveragePercent);


                        $methodNode = $document->createElement('method');

                        $methodNode->appendChild($document->createElement('package', ''));
                        $methodNode->appendChild($document->createElement('className', $className));
                        $methodNode->appendChild($document->createElement('methodName', $methodName));
                        $methodNode->appendChild($document->createElement('methodSignature', $method['signature']));
                        $methodNode->appendChild($document->createElement('fullMethod', sprintf('%s(%s)', $methodName, $method['signature'])));
                        $methodNode->appendChild($document->createElement('crap', $crap));
                        $methodNode->appendChild($document->createElement('complexity', $method['ccn']));
                        $methodNode->appendChild($document->createElement('coverage', $coveragePercent));
                        $methodNode->appendChild($document->createElement('crapLoad', 0));

                        /*
                         *   public int getCrapLoad(float crapThreshold) {
    int crapLoad = 0;
    if (getCrap() >= crapThreshold) {
      int complexity = getComplexity();
      float coverage = getCoverage();
      crapLoad += complexity * (1.0 - coverage);
      crapLoad += complexity / crapThreshold;
    }
    return crapLoad;
  }*/

                        $methodsNode->appendChild($methodNode);

                        $classStatistics['methods']++;

                        if ($methodCount > 0) {
                            $classStatistics['coveredMethods']++;
                        }
                    }

                    /*if (!empty($package['fullPackage'])) {
                        $class->setAttribute(
                          'fullPackage', $package['fullPackage']
                        );
                    }

                    if (!empty($package['category'])) {
                        $class->setAttribute(
                          'category', $package['category']
                        );
                    }

                    if (!empty($package['package'])) {
                        $class->setAttribute(
                          'package', $package['package']
                        );
                    }

                    if (!empty($package['subpackage'])) {
                        $class->setAttribute(
                          'subpackage', $package['subpackage']
                        );
                    }*/
                }
            }
        }

        $root->appendChild($methodsNode);

        $stats = $document->createElement('stats');

        $stats->appendChild($document->createElement('name', 'Method Crap Stats'));

        $stats->appendChild($document->createElement('totalCrap', 0));
        $stats->appendChild($document->createElement('crap', 0));
        $stats->appendChild($document->createElement('median', 0));
        $stats->appendChild($document->createElement('average', 0));
        $stats->appendChild($document->createElement('stdDev', 0));
        $stats->appendChild($document->createElement('methodCount', 0));
        $stats->appendChild($document->createElement('crapMethodCount', 0));
        $stats->appendChild($document->createElement('crapMethodPercent', 0));
        $stats->appendChild($document->createElement('crapLoad', 0));
        $stats->appendChild($document->createElement('crapThreshold', 0));
        $stats->appendChild($document->createElement('crapLoad', 0));
        $stats->appendChild($document->createElement('globalCraploadAverage', 0));
        $stats->appendChild($document->createElement('globalCrapMethodAverage', 0));
        $stats->appendChild($document->createElement('globalTotalMethodAverage', 0));
        $stats->appendChild($document->createElement('globalAverageDiff', 0));
        $stats->appendChild($document->createElement('globalCraploadAverageDiff', 0));
        $stats->appendChild($document->createElement('globalCrapMethodAverageDiff', 0));
        $stats->appendChild($document->createElement('globalTotalMethodAverageDiff', 0));

        $root->appendChild($stats);

        /*

        $metrics = $document->createElement('metrics');

        $metrics->setAttribute('files', $projectStatistics['files']);
        $metrics->setAttribute('loc', $projectStatistics['loc']);
        $metrics->setAttribute('ncloc', $projectStatistics['ncloc']);
        $metrics->setAttribute('classes', $projectStatistics['classes']);
        $metrics->setAttribute('methods', $projectStatistics['methods']);

        $metrics->setAttribute(
          'coveredmethods', $projectStatistics['coveredMethods']
        );

        $metrics->setAttribute(
          'conditionals', $projectStatistics['conditionals']
        );

        $metrics->setAttribute(
          'coveredconditionals', $projectStatistics['coveredConditionals']
        );

        $metrics->setAttribute(
          'statements', $projectStatistics['statements']
        );

        $metrics->setAttribute(
          'coveredstatements', $projectStatistics['coveredStatements']
        );

        $metrics->setAttribute(
          'elements',
          $projectStatistics['conditionals'] +
          $projectStatistics['statements']   +
          $projectStatistics['methods']
        );

        $metrics->setAttribute(
          'coveredelements',
          $projectStatistics['coveredConditionals'] +
          $projectStatistics['coveredStatements']   +
          $projectStatistics['coveredMethods']
        );

        $project->appendChild($metrics);*/

        if ($target !== NULL) {
            if (!is_dir(dirname($target))) {
              mkdir(dirname($target), 0777, TRUE);
            }

            return $document->save($target);
        } else {
            return $document->saveXML();
        }
    }
}