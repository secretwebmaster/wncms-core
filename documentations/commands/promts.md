# Common Prompts

## 功能開發（含文件同步）
**用途（zh_TW）**  
這個 prompt 用來要求 AI 在開發功能前先讀專案規範與對應文件，開發後同步更新 `documentations/manual`，避免程式碼與文件脫節。

```text
Read AGENTS.md and apply all listed skills (including wncms-doc-sync).
Before coding, read the matching docs in documentations/manual for this area.
After code changes, update the corresponding docs in documentations/manual (and zh-CN/zh-TW mirrors when present).
Task: <your task>
Constraints:
- Keep existing architecture and naming.
- Use WNCMS patterns (BaseModel, BackendController, ModelManager).
- List files changed and why.
- If skipping scaffold pieces, explain what was skipped.
```

## 推進下一個 To-Do（自動選擇下一項）
**用途（zh_TW）**  
這個 prompt 用來讓 AI 先閱讀 `AGENTS.md`，再從 `documentations/plans/to-do.md` 挑選下一個最有影響且可落地的未完成項目，直接實作並同步文件。

```text
Read AGENTS.md and apply all listed skills (including wncms-doc-sync).
Before coding, read the matching docs in documentations/manual for this area.
Then read documentations/plans/to-do.md, select the next highest-impact unfinished item, and implement it.
After code changes, update the corresponding docs in documentations/manual (and zh-CN/zh-TW mirrors when present).

Guide me to manually test your update and expected output
After I confirm the tests got expected output:
- move the selected item from documentations/plans/to-do.md to documentations/plans/completed.md.
- keep the same heading structure in completed.md when moving the item.
- remove the moved completed item from documentations/plans/to-do.md.
- run documentations/manual/deploy-doc.sh to deploy the doc.
- group changes into commit commands and show me. Ask me to confirm to run commit.

Task:
- Continue from the to-do list and finish one complete item end-to-end.

Constraints:
- Keep existing architecture and naming.
- Use WNCMS patterns (BaseModel, BackendController, ModelManager).
- Prefer minimal, safe changes with verification steps.
- List files changed and why.
- If skipping scaffold pieces, explain what was skipped.
```
