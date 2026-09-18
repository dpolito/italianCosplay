<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

final class Money
{
	private int $amountInCents;
	private string $currency;

	public function __construct(int $amountInCents, string $currency = 'EUR')
	{
		$this->amountInCents = $amountInCents;
		$this->currency = strtoupper(trim($currency)) ?: 'EUR';
	}

	public static function fromDecimal(float|int|string $amount, string $currency = 'EUR'): self
	{
		if (is_string($amount)) {
			$amount = str_replace(',', '.', trim($amount));
			if ($amount === '' || !is_numeric($amount)) {
				throw new InvalidArgumentException('Importo monetario non valido.');
			}
		}

		return new self((int) round(((float) $amount) * 100), $currency);
	}

	public static function zero(string $currency = 'EUR'): self
	{
		return new self(0, $currency);
	}

	public function add(self $other): self
	{
		$this->assertSameCurrency($other);

		return new self($this->amountInCents + $other->amountInCents, $this->currency);
	}

	public function subtract(self $other): self
	{
		$this->assertSameCurrency($other);

		return new self($this->amountInCents - $other->amountInCents, $this->currency);
	}

	public function multiply(int|float $factor): self
	{
		return new self((int) round($this->amountInCents * $factor), $this->currency);
	}

	public function isGreaterThanZero(): bool
	{
		return $this->amountInCents > 0;
	}

	public function isZero(): bool
	{
		return $this->amountInCents === 0;
	}

	public function getCurrency(): string
	{
		return $this->currency;
	}

	public function getAmountInCents(): int
	{
		return $this->amountInCents;
	}

	public function toDecimal(): float
	{
		return $this->amountInCents / 100;
	}

	public function format(int $decimals = 2, string $decimalSeparator = ',', string $thousandsSeparator = '.'): string
	{
		return number_format($this->toDecimal(), $decimals, $decimalSeparator, $thousandsSeparator);
	}

	public function __toString(): string
	{
		return $this->format() . ' ' . $this->currency;
	}

	private function assertSameCurrency(self $other): void
	{
		if ($this->currency !== $other->currency) {
			throw new InvalidArgumentException('Le valute devono coincidere.');
		}
	}
}
