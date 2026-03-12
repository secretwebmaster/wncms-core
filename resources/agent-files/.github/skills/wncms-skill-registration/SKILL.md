---
name: wncms-skill-registration
description: Keep a host project's AGENTS.md in sync whenever local skills are added, removed, renamed, or repurposed under .github/skills.
---

## Goal
Make project-specific skills discoverable by agents in the current host project.

## Use When
- adding a new project skill under `.github/skills`
- deleting or renaming a project skill
- changing a skill's purpose enough to require new routing text in `AGENTS.md`

## Hard Rules
- Update the current project's `AGENTS.md` whenever the local skill set changes.
- Add new skills to `Skills To Apply`.
- Add or revise `Skill Routing` so the trigger for the skill is explicit.
- Remove stale entries for deleted or renamed skills.
- Keep skill paths rooted at `.github/skills/{skill_name}/SKILL.md`.
- Do not list skills that do not exist on disk.
- Do not use AGENTS routing text to invent undocumented WNCMS runtime behavior; routing should only tell the agent when to read the skill.

## Required Workflow
1. Inspect the current `.github/skills/` directory and identify the added, removed, renamed, or repurposed skill.
2. Update `AGENTS.md`:
   - add/remove the skill in `Skills To Apply`
   - add or adjust the routing bullets for the skill
3. Re-read `AGENTS.md` and confirm every listed skill path exists.

## Quick Validation Checklist
- [ ] `AGENTS.md` mentions every current local skill that should be auto-routed.
- [ ] Removed or renamed skills no longer appear.
- [ ] Routing text is specific enough to trigger the right skill.
