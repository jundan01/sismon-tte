@extends('errors.layout')

@section('title', 'Terlalu Banyak Permintaan')
@section('border-color', '#f1c21b')
@section('code', '429')
@section('heading', 'Batas Frekuensi Terlampaui (Too Many Requests)')
@section('message', 'Sistem mendeteksi terlalu banyak permintaan dari alamat IP Anda dalam periode waktu singkat. Silakan tunggu ' . ($retryAfter ?? 'beberapa') . ' detik sebelum mencoba kembali demi stabilitas sistem.')
