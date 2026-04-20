#!/usr/bin/env python3
import base64


def caesar_letters(text: str, shift: int) -> str:
	result = []
	for character in text:
		if "a" <= character <= "z":
			result.append(chr((ord(character) - ord("a") + shift) % 26 + ord("a")))
		elif "A" <= character <= "Z":
			result.append(chr((ord(character) - ord("A") + shift) % 26 + ord("A")))
		else:
			result.append(character)

	return "".join(result)


def main() -> int:
	text = input("Texto: ").strip()
	if not text:
		print("entrada vacia")
		return 1

	step1 = caesar_letters(text, 16)
	step2 = step1[::-1]
	step3 = base64.b64encode(step2.encode()).decode()
	step4 = caesar_letters(step3, 7)
	step5 = base64.b64decode(step4.encode())

	print(step5.hex())

	return 0


if __name__ == "__main__":
	raise SystemExit(main())
