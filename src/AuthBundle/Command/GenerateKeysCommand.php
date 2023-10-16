<?php

/*
 * This file is part of the RCH package.
 *
 * (c) Robin Chalas <https://github.com/chalasr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AuthBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Generates SSL Keys for LexikJWT.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
class GenerateKeysCommand extends ContainerAwareCommand
{
    private $io;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('auth:jwt:generate-keys')
            ->setDescription('Generate SSL keys to be consumed by LexikJWTAuthenticationBundle');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $fs = new FileSystem();

        $this->io->title('RCHJWTUserBundle - Generate SSL Keys');

        $privatePath = $this->getContainer()->getParameter('lexik_jwt_authentication.private_key_path');
        $publicPath = $this->getContainer()->getParameter('lexik_jwt_authentication.public_key_path');
        $passphrase = $this->getContainer()->getParameter('lexik_jwt_authentication.pass_phrase');

        $this->generatePrivateKey($privatePath, $passphrase, $this->io);
        $this->generatePublicKey($privatePath, $publicPath, $passphrase, $this->io);

        $outputMessage = 'RSA keys successfully generated';

        if ($passphrase) {
            $outputMessage .= $this->io->getFormatter()->format(
                sprintf(' with passphrase <comment>%s</comment></info>', $passphrase)
            );
        }

        $this->io->success($outputMessage);
    }

    /**
     * Generate a RSA private key.
     *
     * @param string          $path
     * @param string          $passphrase
     * @param OutputInterface $output
     *
     * @throws ProcessFailedException
     */
    protected function generatePrivateKey($privatePath, $passphrase)
    {
        if ($passphrase) {
            $processArgs = sprintf('genrsa -out %s -aes256 -passout pass:%s 4096', $privatePath, $passphrase);
        } else {
            $processArgs = sprintf('genrsa -out %s 4096', $privatePath);
        }

        $this->generateKey($processArgs);
    }

    /**
     * Generate a RSA public key.
     *
     * @param string          $path
     * @param string          $passphrase
     * @param OutputInterface $output
     */
    protected function generatePublicKey($privatePath, $publicPath, $passphrase)
    {
        $processArgs = sprintf('rsa -pubout -in %s -out %s -passin pass:%s', $privatePath, $publicPath, $passphrase);

        $this->generateKey($processArgs);
    }

    /**
     * Generate a RSA key.
     *
     * @param string          $processArgs
     * @param Outputinterface $output
     *
     * @throws ProcessFailedException
     */
    protected function generateKey($processArgs)
    {
        $process = new Process(sprintf('openssl %s', $processArgs));
        $process->setTimeout(3600);

        $process->run(function ($type, $buffer) {
            $this->io->write($buffer);
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $process->getExitCode();
    }
}