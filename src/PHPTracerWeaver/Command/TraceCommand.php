<?php namespace PHPTracerWeaver\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TraceCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_ERROR = 1;

    /**
     * Set up command parameteres and help message.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('trace')
            ->setDescription('Traces function signatures in a running PHP script')
            ->addArgument('phpscript', InputArgument::REQUIRED, 'A PHP script to execute and trace')
            ->addArgument('options', InputArgument::OPTIONAL, 'The PHP script is launched with these options')
            ->addOption('tracefile', null, InputOption::VALUE_OPTIONAL, 'Where to save trace', 'dumpfile')
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append to an existing tracefile')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command will execute a PHP script at save the trace data to a file:

    <info>%command.full_name% vendor/bin/phpunit</info>

You can specify parameteres to be passed to the script as the secound argument:

    <info>%command.full_name% vendor/bin/phpunit ' -c tests/phpunit.xml'</info>

By default the trace will besaved to dumpfile.xt, but you can also specify a path (.xt is automattically appended):

    <info>%command.full_name% vendor/bin/phpunit --tracefile=traces/unitest</info>
EOT
        );
    }

    /**
     * Run the trace process.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $tracefile = $input->getOption('tracefile');
        $append = $input->getOption('append');
        $phpscript = $input->getArgument('phpscript');
        $options = $input->getArgument('options');

        $command = 'php';

        $params = [
            '-d xdebug.auto_trace'               => 1,
            '-d xdebug.trace_options'            => (int) $append,
            '-d xdebug.trace_output_dir'         => getcwd(),
            '-d xdebug.trace_output_name'        => $tracefile,
            '-d xdebug.trace_format'             => 0,
            '-d xdebug.collect_params'           => 1,
            '-d xdebug.collect_return'           => 1,
        ];
        foreach ($params as $param => $value) {
            $command .= ' ' . $param . '=' . $value;
        }
        $command .= ' ' . $phpscript . $options;

        $output->writeln('Running script with instrumentation: ' . $phpscript . $options);
        passthru($command);
        $output->writeln('TRACE COMPLETE');

        return self::RETURN_CODE_OK;
    }
}
