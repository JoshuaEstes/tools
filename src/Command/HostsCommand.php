<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HostsCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('hosts')
            ->setDescription('Manage hosts file')
            ->setDefinition(array(
                new InputOption('file', 'f', InputOption::VALUE_REQUIRED, 'Location of hosts file', '/etc/hosts'),
                new InputOption('list', 'l', InputOption::VALUE_NONE, 'List active entries'),
                new InputOption('list-all', 'a', InputOption::VALUE_NONE, 'List all entries'),
                new InputOption('enable', 'e', InputOption::VALUE_NONE, 'Enable an entry'),
                new InputOption('disable', 'd', InputOption::VALUE_NONE, 'Disable an entry'),
                new InputOption('add', null, InputOption::VALUE_NONE, 'Add a new entry'),
                new InputOption('delete', null, InputOption::VALUE_NONE, 'Delete an entry'),
                new InputOption('ip', null, InputOption::VALUE_REQUIRED, 'IP Address', '127.0.0.1'),
                new InputArgument('hostnames', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'List of hostnames'),
            ))
            ->setHelp(<<<EOF
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('list') || $input->getOption('list-all')) {
            $hosts = $this->parseHostsFromFile($input->getOption('file'), $input->getOption('list-all'));
            foreach ($hosts as $ip => $hostnames) {
                $output->writeln(sprintf('IP Address: %s', $ip));
                $line = '';
                foreach ($hostnames as $hostname) {
                    $line .= $hostname . ', ';
                }
                $output->writeln(array(sprintf("Hostnames:\n    %s", substr($line, 0, -2)), ''));
            }

            return;
        }

    }

    /**
     * Parses a file and returns an array of all the hosts it was able to find
     * in the file
     *
     * @param string $file
     * @return array
     */
    private function parseHostsFromFile($file, $include_comments = false)
    {
        $hosts   = array();
        $handler = fopen($file, 'r');
        while(!feof($handler) && false !== ($buffer = fgets($handler, 1024))) {
            $buffer = trim($buffer); // remove any whitespace/line returns
            if ('#' === substr($buffer, 0, 1)) {
                if ($include_comments) { // include the commented out ones
                    $buffer = substr($buffer, 1);
                } else {
                    continue;
                }
            }
            $line  = array();
            $dirty = preg_split('/( |\t)/', $buffer);
            foreach ($dirty as $entry) {
                if (!empty($entry)) {
                    $line[] = $entry;
                }
            }
            if (!empty($line)) {
                $hosts[] = $line;
            }

        }
        fclose($handler);

        return $this->sortHostsByIp($hosts);
    }

    /**
     * @param string $hosts
     * @return array
     */
    private function sortHostsByIp($hosts)
    {
        $clean = array();
        foreach ($hosts as $entry) {
            $ip = $entry[0];
            unset($entry[0]);
            foreach ($entry as $hostname) {
                $clean[$ip][] = $hostname;
            }
            sort($clean[$ip]); // sorts hostnames
        }
        ksort($clean); // sorts by ip address

        return $clean;
    }

}
