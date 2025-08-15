@extends('admin._layoutNew')

@section('page-head')
    <style type="text/css">
        
    </style>
@endsection

@section('page-content')
    <div class="layui-inline">
        <div class="layui-input-inline" style="margin-left: 50px;">
            <input type="text" name="user_id" autocomplete="off" id="user_id" placeholder="请输入用户ID" class="layui-input" value="">
       </div>
       <div class="layui-input-inline date_time111" style="margin-left: 50px;">
           <input type="text" name="start_time" id="start_time" placeholder="请输入开始时间" autocomplete="off" class="layui-input" value="">
       </div>
       <div class="layui-input-inline date_time111" style="margin-left: 50px;">
           <input type="text" name="end_time" id="end_time" placeholder="请输入结束时间" autocomplete="off" class="layui-input" value="">
       </div>
        <button class="layui-btn btn-search" id="mobile_search" lay-submit lay-filter="mobile_search"> <i class="layui-icon">&#xe615;</i> </button>
    </div>
    
    <table class="layui-hide" id="mailMessageList" lay-filter="mailMessageList"></table>
    
    <script type="text/html" id="barDemo">
        @{{# if (d.status == 0) { }}
            <a class="layui-btn layui-btn-xs" lay-event="tg">通过</a>
            <a class="layui-btn layui-btn-xs" lay-event="jj">拒绝</a>
        @{{# } }}
         
    </script>
    <script type="text/html" id="housing_img">
        <a href="javascript:void(0);" onclick="showImg('{{$imageServerUrl}}@{{d.housing_img}}')">查看</a>
    </script>
    <script type="text/html" id="income_certificate_img">
        <a href="javascript:void(0);" onclick="showImg('{{$imageServerUrl}}@{{d.income_certificate_img}}')">查看</a>
    </script>
    <script type="text/html" id="bank_details_img">
        <a href="javascript:void(0);" onclick="showImg('{{$imageServerUrl}}@{{d.bank_details_img}}')">查看</a>
    </script>
    <script type="text/html" id="id_img">
        <a href="javascript:void(0);" onclick="showImg('{{$imageServerUrl}}@{{d.id_img}}')">查看</a>
    </script>
    
    <script type="text/html" id="statustml">
        @{{d.status==0 ? '<span class="layui-badge status_bg_1">'+'未审核'+'</span>' : '' }}
        @{{d.status==1 ? '<span class="layui-badge status_bg_2">'+'审核通过'+'</span>' : '' }}
        @{{d.status==2 ? '<span class="layui-badge status_bg_3">'+'审核失败'+'</span>' : '' }}

    </script>
@endsection

@section('scripts')

    <script>
        function showImg(url){
            layer.open({
                type: 1,
                title: "图片查看",
                area: ['500px', '600px'], //宽高
                content: "<img src=' "+ url +" '>"
            });
        }
                window.onload = function() {
                    document.onkeydown=function(event){
                        var e = event || window.event || arguments.callee.caller.arguments[0];
                        if(e && e.keyCode==13){ // enter 键
                            $('#mobile_search').click();
                        }
                    };
                    layui.use(['element', 'form', 'layer', 'table','laydate'], function () {
                        var element = layui.element;
                        var layer = layui.layer;
                        var table = layui.table;
                        var $ = layui.$;
                        var form = layui.form;
                        var laydate = layui.laydate;

                        laydate.render({
                            elem: '#start_time'
                        });
                        laydate.render({
                            elem: '#end_time'
                        });

                        form.on('submit(mobile_search)',function(obj){
                            var start_time =  $("#start_time").val()
                            var end_time =  $("#end_time").val()
                            var user_id =  $("#user_id").val()
                            tbRend("{{url('/admin/loan/order_list')}}?user_id="+user_id
                                +'&start_time='+start_time
                                +'&end_time='+end_time
                            );
                            return false;
                        });
                        
                        
                        
                        function tbRend(url) {
                            table.render({
                                elem: '#mailMessageList'
                                ,url: url
                                ,page: true
                                ,limit: 20
                                ,height: 'full-100'
                                ,toolbar: true
                                ,cols: [[
                                    {field: 'id', title: 'ID'}
                                    ,{field:'user_id',title: '用户编号'}
                                    ,{field:'mechanism_name',title: '借款名称'}
                                    ,{field:'mechanism_day',title: '借款天数'}
                                    ,{field:'mechanism_rate',title: '借款利率'}
                                    ,{field:'amount',title:'借款金额'}
                                    ,{field:'currency_code',title:'借款币种'}
                                    ,{field:'interest',title:'借款利息'}
                                    ,{field:'housing_img',title:'房屋信息',templet: '#housing_img'}
                                    ,{field:'income_certificate_img',title:'收入证明（雇佣关系）',templet: '#income_certificate_img'}
                                    ,{field:'bank_details_img',title:'银行明细',templet: '#bank_details_img'}
                                    ,{field:'id_img',title:'证件照',templet: '#id_img'}
                                    ,{field:'status',title: '状态', templet: '#statustml'}
                                    ,{fixed: 'right', title: '操作', minWidth: 150, align: 'center', toolbar: '#barDemo'}
                                ]]
                            });
                        }
                        tbRend("{{url('/admin/loan/order_list')}}");
                        //监听工具条
                        table.on('tool(mailMessageList)', function (obj) { //注：tool是工具条事件名，test是table原始容器的属性 lay-filter="对应的值"
                            var data = obj.data;
                            var layEvent = obj.event;
                            var tr = obj.tr;

                            if (layEvent === 'tg') { //编辑
                                layer.confirm('确认通过审核吗？', function(index){
                                    //向服务端发送删除指令
                                    $.ajax({
                                        url:'/admin/loan/order_pass',
                                        type:'post',
                                        dataType:'json',
                                        data:{id:data.id},
                                        success:function(res){
                                            if(res.type=='ok'){
                                                layer.msg(res.message);
                                                layer.close(index);
                                                tbRend("{{url('/admin/loan/order_list')}}");
                                            }else{
                                                layer.close(index);
                                                layer.alert(res.message);
                                            }
                                        }
                                    });
                                });
                            }
                            
                            if (layEvent === 'jj') { //编辑
                                layer.prompt({
                                    title: '请输入驳回理由：',
                                    value: '',
                                    formType: 2
                                }, function (name, index) {
                                    $.ajax({
                                        url:'/admin/loan/order_nopass',
                                        type:'post',
                                        dataType:'json',
                                        data:{id:data.id,value:name},
                                        success:function(res){
                                            if(res.type=='ok'){
                                                layer.msg(res.message);
                                                layer.close(index);
                                                tbRend("{{url('/admin/loan/order_list')}}");
                                            }else{
                                                layer.close(index);
                                                layer.alert(res.message);
                                            }
                                        }
                                    });
                                   
                                });

                            }
                            
                        });
                    });
                }
            </script>    
@endsection