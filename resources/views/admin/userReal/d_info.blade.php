@extends('admin._layoutNew')

@section('page-head')

@endsection

@section('page-content')
    <form class="layui-form" action="">
        <div class="layui-form-item">
            <label class="layui-form-label">用户手机号或邮箱</label>
            <div class="layui-input-block">
                <input type="text" name="account" readonly="readonly" autocomplete="off" placeholder="" class="layui-input" value="{{$result->account}}" disabled>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">证件类型</label>
            <div class="layui-input-block">
                <input type="text" name="email" readonly="readonly" autocomplete="off" placeholder="" class="layui-input" value="@switch($result->id_type)
                @case(0)身份证@break
                @case(1)护照@break
                @case(2)驾驶证@break
                @endswitch" disabled>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">真实姓名</label>
            <div class="layui-input-block">
                <input type="text" name="email" readonly="readonly" autocomplete="off" placeholder="" class="layui-input" value="{{$result->name}}" disabled>
            </div>
        </div>
       

        <div class="layui-form-item">
            <label class="layui-form-label">身份证号码</label>
            <div class="layui-input-block">
                <input type="text" name="card_id" readonly="readonly" autocomplete="off" placeholder="" class="layui-input" value="{{$result->card_id}}">
            </div>
        </div>
        <div class="layui-form-item layui-form-text">
            <label class="layui-form-label">正面照片</label>
            <div class="layui-input-block upload-box">
                <button class="layui-btn upload_test" type="button">选择图片</button>
                <button class="layui-btn layui-btn-normal clear_upload" type="button" style="display:none">清除图片</button>
                <br>
                <img src="" class="thumbnail" style="max-width: 200px;height: auto;margin-top: 5px;">
                <input type="hidden" name="front_pic" class="thumb-input" value="">
                
            </div>
        </div>
         <div class="layui-form-item layui-form-text">
            <label class="layui-form-label">反面照片</label>
            <div class="layui-input-block upload-box">
                <button class="layui-btn upload_test" type="button">选择图片</button>
                <button class="layui-btn layui-btn-normal clear_upload" type="button" style="display:none">清除图片</button>
                <br>
                
                <img src="" class="thumbnail" style="max-width: 200px;height: auto;margin-top: 5px;">
                <input type="hidden" name="reverse_pic" class="thumb-input" value="">
               
            </div>
        </div>
         <input type="hidden" name="id" value="{{$result->id}}">
         <input type="hidden" id="upload_url" value="{{$imageServerUrl}}" />
        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="demo1">立即提交</button>
            </div>
        </div>
        
        
    </form>

@endsection
@section('scripts')
    <script>


         layui.use(['element', 'form','table', 'upload', 'layer', 'laydate'], function () {
            var form = layui.form
                ,$ = layui.jquery
                ,laydate = layui.laydate
                ,upload = layui.upload
                ,index = parent.layer.getFrameIndex(window.name);
                var uploadInst = upload.render({
                    elem: '.upload_test' //绑定元素
                    ,url: '{{URL("api/upload_new")}}?scene=admin' //上传接口
                    ,done: function(res){
                        //上传完毕回调
                        if (res.type == "ok"){
                            var pbox = $(this.item).closest('.upload-box');
                            pbox.find(".thumb-input").val(res.message);
                            pbox.find('.clear_upload').show();
                            pbox.find(".thumbnail").show();
                            pbox.find(".thumbnail").attr("src",$("#upload_url").val() + res.message)
                        } else{
                            alert(res.message)
                        }
                    }
                    ,error: function(){
                        //请求异常回调
                    }
                });
                $('.clear_upload').click(function() {
                    var pbox = $(this).closest('.upload-box');
                    pbox.find(".thumb-input").val('');
                    pbox.find('.clear_upload').hide();
                    pbox.find(".thumbnail").hide();
                    pbox.find(".thumbnail").attr("src",'');
                });
            //监听提交
            form.on('submit(demo1)', function(data){
                var data = data.field;
                $.ajax({
                    url:'{{url('admin/user/d_real_info')}}'
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
