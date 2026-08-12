---
paths:
  - 'app/Http/Controllers/Simulation/**'
---

# Simulation

## Phishing portal must never receive typed credentials
The fake portal (resources/js/pages/phishing/portal.tsx) submits ONLY { action, keystroke_detected } — never the email/password field values. Keep it that way. IPs are hashed at capture time in SimulationRecorder (never stored raw), and the raw User-Agent is parsed to coarse features then discarded. Do not add logging of the behavior request body.
