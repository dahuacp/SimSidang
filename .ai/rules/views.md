---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## Tombol non-submit di dalam form harus type=\"button\"
Setiap <button> di dalam <form> yang berfungsi interaksi (mis. hasil dropdown Alpine searchable-select, tombol close modal) WALIB diberi type=\"button\". Tanpa itu HTML default type=\"submit\" aktif dan mengklik tombol akan submit form (sering dengan field kosong) lalu reload, membuat interaksi tampak \"tidak terjadi\". Bug ini menimpa plotting mahasiswa di admin/schedules (fix 2026-08-17, commit 0c324d6).
