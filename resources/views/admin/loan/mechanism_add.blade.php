@extends('admin._layoutNew')

@section('page-head')
<style>
    .layui-form-label {
        width: 150px;
    }
    .layui-input-block {
        margin-left: 180px;
    }
    .layui-form-select dl { z-index: 9999; }
</style>
@endsection

@section('page-content')
    <form class="layui-form" action="">
        <div class="layui-form-item">
            <label class="layui-form-label">机构名称</label>
            <div class="layui-input-block">
                <input type="text" name="name" autocomplete="off" lay-filter="required" placeholder="" class="layui-input" value="{{$loan_mechanism['name']}}">
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">币种</label>
            <div class="layui-input-block">
                <select name="currency" lay-filter="required">
                    <option value="BTC" @if($loan_mechanism->currency_code == 'BTC') selected @endif>BTC</option>
                    <option value="ETH" @if($loan_mechanism->currency_code == 'ETH') selected @endif>ETH</option>
                    <option value="USDT" @if($loan_mechanism->currency_code == 'USDT') selected @endif>USDT</option>
                </select>
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">天数</label>
            <div class="layui-input-block">
                 <input type="text" name="day" autocomplete="off" lay-filter="required" placeholder="" class="layui-input" value="{{$loan_mechanism['day']}}">
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">利率</label>
            <div class="layui-input-block">
                <input type="text" name="rate" autocomplete="off" lay-filter="required" placeholder="" class="layui-input" value="{{$loan_mechanism['rate']}}">
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">最大额度</label>
            <div class="layui-input-block">
                <input type="text" name="min" autocomplete="off" lay-filter="required" placeholder="" class="layui-input" value="{{$loan_mechanism['min']}}">
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">最小额度</label>
            <div class="layui-input-block">
                <input type="text" name="max" autocomplete="off" lay-filter="required" placeholder="" class="layui-input" value="{{$loan_mechanism['max']}}">
            </div>
        </div>
        
        <input type="hidden" name="id" autocomplete="off" lay-filter="required" placeholder="" class="layui-input" value="{{$loan_mechanism['id']}}">
        
        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="demo1">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </form>

@endsection

@section('scripts')
<script type="text/javascript" src="{{ URL('vendor/ueditor/1.4.3/ueditor.config.js') }}"></script>
<script type="text/javascript" src="{{ URL('vendor/ueditor/1.4.3/ueditor.all.js') }}"> </script>
<script type="text/javascript" src="{{ URL('vendor/ueditor/1.4.3/lang/zh-cn/zh-cn.js') }}"></script>
<script type="text/javascript" src="{{URL("/admin/js/newsFormSubmit.js?v=").time()}}"></script>
    <script>
        layui.use(['form','laydate'],function () {
            var form = layui.form
                ,$ = layui.jquery
                ,laydate = layui.laydate
                ,index = parent.layer.getFrameIndex(window.name);
            
            form.on('submit(demo1)', function(data){
                var data = data.field;
                $.ajax({
                    url:'{{url('admin/loan/mechanism_add')}}'
                    ,type:'post'
                    ,dataType:'json'
                    ,data : data
                    ,success:function(res){
                        if(res.type=='error'){
                            layer.msg(res.message);
                        }else{
                            parent.layer.close(index);
                            parent.window.location.reload();
                        }
                    }
                });
                return false;
            });
        });
    </script>

@endsection