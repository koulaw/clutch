---
paths:
  - 'app/{AnalysisStatus.php,Models/{Demo,Analysis,GameMatch,GameRound,Player,Artifact}.php}'
---

# App

## Use the demo analysis domain graph
A user owns demos; each demo may have numbered analysis attempts, each analysis has at most one GameMatch, and matches own GameRounds and attach reusable Players. Artifacts belong to an analysis and optionally a round. Use AnalysisStatus and its allowed transitions for the uploaded-to-ready lifecycle.
