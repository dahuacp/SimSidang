---
paths:
  - 'resources/views/dosen/penilaian/**'
---

# Penilaian

## Don't x-model hidden item-index inputs in penilaian form
The _form.blade.php penilaian form has a hidden input named skor_per_item[i][item] with :value="idx". It must NOT have x-model (that overwrites skors[i]); only the visible skor input uses x-model="skors[idx]". Template items are cast to array, so wrap with collect(...)->map() in the view.
