@extends('layouts.app')
@section('page_title')
    Roulettes - MoviePickr
@endsection
@section('scripts')
@endsection
@section('content')
<div class="container content" style="padding-top: 80px;">

    <div class="col-lg-12">
        <h1 class="font-weight-bold text-center">Movie Roulettes</h1>
        <hr class="divider my-4" />
    </div>

    {{--  First Row  --}}
    <div class="card-deck mb-3">
        <div class="card">
{{--            <img class="card-img-top" src="..." alt="Card image cap">--}}
            <div class="card-img-top" style="position: relative; height: 200px; background-image: url('https://static1.srcdn.com/wordpress/wp-content/uploads/2019/10/the-grudge-banner.jpg?q=50&fit=crop&w=740&h=370&dpr=1.5'); background-size: cover">
                <img src="https://cdn4.iconfinder.com/data/icons/logos-and-brands/512/227_Netflix_logo-512.png" style="position: absolute; right: 10px; bottom: 10px; height: 50px">
            </div>
            <div class="card-body">
                <h5 class="card-title">Netflix horror</h5>
                <p class="card-text">This is a longer card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
            </div>
            <div class="card-footer">
                <a href="/roulettes/netflix/horror" class="btn btn-secondary">Roll</a>
            </div>
        </div>
        <div class="card">
{{--            <img class="card-img-top" src="..." alt="Card image cap">--}}
            <div class="card-img-top" style="position: relative; height: 200px; background-image: url('http://hawkeyefilms.ca/wpsite/wp-content/uploads/2018/07/Documentary-Banner.jpg'); background-size: cover">
                <img src="https://cdn4.iconfinder.com/data/icons/logos-and-brands/512/227_Netflix_logo-512.png" style="position: absolute; right: 10px; bottom: 10px; height: 50px">
            </div>
            <div class="card-body">
                <h5 class="card-title">Netflix documentaries</h5>
                <p class="card-text">This card has supporting text below as a natural lead-in to additional content.</p>
            </div>
            <div class="card-footer">
                <a href="/roulettes/netflix/doc" class="btn btn-secondary">Roll</a>
            </div>
        </div>
        <div class="card">
{{--            <img class="card-img-top" src="..." alt="Card image cap">--}}
            <div class="card-img-top" style="position: relative; height: 200px; background-image: url('https://i.pinimg.com/originals/4c/8e/26/4c8e267ee4446e733bb17564337083f7.jpg'); background-size: cover">
                <img src="https://cdn4.iconfinder.com/data/icons/logos-and-brands/512/227_Netflix_logo-512.png" style="position: absolute; right: 10px; bottom: 10px; height: 50px">
            </div>
            <div class="card-body">
                <h5 class="card-title">Netflix anime movies</h5>
                <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This card has even longer content than the first to show that equal height action.</p>
            </div>
            <div class="card-footer">
                <a href="/roulettes/netflix/animovies" class="btn btn-secondary">Roll</a>
            </div>
        </div>
    </div>
    {{--  Second Row  --}}
    <div class="card-deck">
        <div class="card">
{{--            <img class="card-img-top" src="..." alt="Card image cap">--}}
            <div class="card-img-top" style="height: 200px; background: black"></div>
            <div class="card-body">
                <h5 class="card-title">Card title</h5>
                <p class="card-text">This is a longer card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p>
            </div>
            <div class="card-footer">
                <a href="#" class="btn btn-secondary">Go somewhere</a>
            </div>
        </div>
        <div class="card">
{{--            <img class="card-img-top" src="..." alt="Card image cap">--}}
            <div class="card-img-top" style="height: 200px; background: black"></div>
            <div class="card-body">
                <h5 class="card-title">Card title</h5>
                <p class="card-text">This card has supporting text below as a natural lead-in to additional content.</p>
            </div>
            <div class="card-footer">
                <a href="#" class="btn btn-secondary">Go somewhere</a>
            </div>
        </div>
        <div class="card">
{{--            <img class="card-img-top" src="..." alt="Card image cap">--}}
            <div class="card-img-top" style="height: 200px; background: black"></div>
            <div class="card-body">
                <h5 class="card-title">Card title</h5>
                <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This card has even longer content than the first to show that equal height action.</p>
            </div>
            <div class="card-footer">
                <a href="#" class="btn btn-secondary">Go somewhere</a>
            </div>
        </div>
    </div>
</div>
@endsection
