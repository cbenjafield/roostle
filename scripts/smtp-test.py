#!/usr/bin/env python3
"""Send a test message through a Roostle SMTP inbox.

Usage:
    python3 scripts/smtp-test.py --user rstxxxxxxxxxxxxxxxx --password ...

The username and password fall back to RST_USER and RST_PASS, which keeps
the password out of shell history and the process list.
"""

import argparse
import base64
import getpass
import mimetypes
import os
import re
import smtplib
import sys
from email.message import EmailMessage

AUTH_COMMAND = re.compile(r"^(send: ')(AUTH (?:PLAIN|LOGIN)) [A-Za-z0-9+/=]+(\\r\\n'\s*)$")
BARE_BASE64 = re.compile(r"^(send: ')[A-Za-z0-9+/=]+(\\r\\n'\s*)$")


class RedactingStream:
    """Wrap stderr so smtplib's debug output never shows credentials.

    AUTH PLAIN and AUTH LOGIN carry the username and password as base64, which
    anyone can decode. Bare base64 lines are only redacted straight after a 334
    challenge, so commands such as QUIT or STARTTLS are left alone.
    """

    def __init__(self, stream) -> None:
        self.stream = stream
        self.buffer = ""
        self.after_challenge = False

    def write(self, text: str) -> int:
        self.buffer += text
        *lines, self.buffer = self.buffer.split("\n")

        for line in lines:
            self.stream.write(self.redact(line) + "\n")

        return len(text)

    def flush(self) -> None:
        self.stream.flush()

    def redact(self, line: str) -> str:
        if self.after_challenge and BARE_BASE64.match(line):
            self.after_challenge = False

            return BARE_BASE64.sub(r"\1[redacted]\2", line)

        self.after_challenge = line.startswith(("reply: b'334", "reply: retcode (334"))

        return AUTH_COMMAND.sub(r"\1\2 [redacted]\3", line)


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Send a test message through a Roostle SMTP inbox.")
    parser.add_argument("--host", default=os.environ.get("RST_HOST", "smtp.roostle.co.uk"))
    parser.add_argument("--port", type=int, default=int(os.environ.get("RST_PORT", "587")))
    parser.add_argument("--user", default=os.environ.get("RST_USER"), help="SMTP username (or RST_USER)")
    parser.add_argument(
        "--password",
        default=os.environ.get("RST_PASS"),
        help="SMTP password (or RST_PASS); prompted for if omitted",
    )
    parser.add_argument("--from", dest="sender", default="app@example.com")
    parser.add_argument("--to", dest="recipient", default="anyone@example.org")
    parser.add_argument("--subject", default="Authenticated hello")
    parser.add_argument("--size", type=int, metavar="KB", help="pad the body to roughly this many kilobytes")
    parser.add_argument("--attach", metavar="PATH", help="attach a file to the message")
    parser.add_argument("--no-starttls", action="store_true", help="skip STARTTLS to check for the 530 reply")

    return parser.parse_args()


def main() -> int:
    arguments = parse_arguments()

    if not arguments.user:
        print("Provide --user or set RST_USER.", file=sys.stderr)
        return 2

    password = arguments.password or getpass.getpass("SMTP password: ")

    sys.stderr = RedactingStream(sys.stderr)

    message = EmailMessage()
    message["From"] = arguments.sender
    message["To"] = arguments.recipient
    message["Subject"] = arguments.subject
    message.set_content("Sent through the Roostle SMTP inbox.\n" + ("x" * 75 + "\n") * ((arguments.size or 0) * 1024 // 76))
    message.add_alternative("<h1>Hello</h1><p>HTML part.</p>", subtype="html")

    if arguments.attach:
        add_attachment(message, arguments.attach)

    try:
        with smtplib.SMTP(arguments.host, arguments.port) as smtp:
            smtp.set_debuglevel(1)

            if arguments.no_starttls:
                return send_raw_auth(smtp, arguments.user, password)

            smtp.starttls()
            smtp.login(arguments.user, password)
            smtp.send_message(message)
    except smtplib.SMTPException as error:
        print(f"FAILED: {error}", file=sys.stderr)

        return 1

    print("OK: message accepted")

    return 0


def add_attachment(message: EmailMessage, path: str) -> None:
    content_type, _ = mimetypes.guess_type(path)
    main_type, sub_type = (content_type or "application/octet-stream").split("/", 1)

    with open(path, "rb") as file:
        message.add_attachment(file.read(), maintype=main_type, subtype=sub_type, filename=os.path.basename(path))


def send_raw_auth(smtp: smtplib.SMTP, user: str, password: str) -> int:
    """Send AUTH PLAIN without STARTTLS, which smtplib.login() refuses to do."""
    smtp.ehlo()
    credentials = base64.b64encode(f"\0{user}\0{password}".encode()).decode()
    code, reply = smtp.docmd("AUTH", f"PLAIN {credentials}")

    print(f"{'OK' if code == 235 else 'FAILED'}: {code} {reply.decode()}")

    return 0 if code == 235 else 1


if __name__ == "__main__":
    sys.exit(main())
