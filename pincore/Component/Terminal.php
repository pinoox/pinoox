<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Component;

use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class Terminal extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return Command::SUCCESS;
    }

    protected function info($message, $newLine = true)
    {
        $this->output->write($message);
        if ($newLine) $this->newline();
    }

    #[NoReturn] protected function error($message, $newLine = true): void
    {
        $this->output->write("<error>$message</error>");
        if ($newLine) $this->newline();
        exit;
    }

    protected function success($message, $newLine = true): void
    {
        $this->output->write("<info>$message</info>");
        if ($newLine) $this->newline();
    }

    protected function question($message, $newLine = true): void
    {
        $this->output->write("<question>$message</question>");
        if ($newLine) $this->newline();
    }

    protected function warning($message, $newLine = true): void
    {
        $this->output->write("<comment>$message</comment>");
        if ($newLine) $this->newline();
    }

    #[NoReturn] protected function newline(): void
    {
        $this->output->writeln('');
    }

    #[NoReturn] protected function stop(): void
    {
        exit;
    }

    protected function table($columns, $rows)
    {
        $table = new Table($this->output);
        $table->setHeaders($columns)
            ->setRows($rows);
        $table->render();
    }

    protected function confirm(string $message, InputInterface $input, OutputInterface $output): bool
    {
        $io = new SymfonyStyle($input, $output);

        $question = new ConfirmationQuestion($message, false);

        return $io->askQuestion($question);
    }


}