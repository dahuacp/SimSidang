---
paths:
  - 'app/Http/Controllers/Dosen/**'
---

# Dosen

## Use Gate ability assess-penilaian, not authorize() for cross-model policies
In Dosen/PenilaianController, authorization checks submission+tipe via AssessmentFormPolicy but authorize('assess', [$submission,$tipe]) resolves policy from Submission (SubmissionPolicy). Register Gate::define('assess-penilaian', ...) in AppServiceProvider::boot() and call abort_unless(Gate::allows('assess-penilaian', [$submission,$tipe]), 403).
