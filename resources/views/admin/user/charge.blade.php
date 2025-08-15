@extends('admin._layoutNew')

@section('page-head')

@endsection

@section('page-content')
<style>
    .status_bg_1{
        background: #1E9FFF;
    }
    .status_bg_2{
        background: #5fb878;
    }
    .status_bg_3{
        background: #ff5722;
    }
</style>
    <div style="margin-top: 10px;width: 100%;">
        

        <form class="layui-form layui-form-pane layui-inline" action="">

            <div class="layui-inline">
                <label class="layui-form-label">用户名</label>
                <div class="layui-input-inline" style="margin-left: 10px">
                    <input type="text" name="account_name" autocomplete="off" class="layui-input">
                </div>
            </div>
            <div class="layui-inline">
                <label class="layui-form-label">充值类型</label>
                <div class="layui-input-inline" style="margin-left: 10px">
                    <select name="is_admin">
                        <option value=""></option>
                        <option value="0">用户充值</option>
                        <option value="1">人工上分</option>
                    </select>
                </div>
            </div>
            <div class="layui-inline">
                <label class="layui-form-label" style="width: 200px;">到账账户类型</label>
                <div class="layui-input-inline" style="margin-left: 10px">
                    <select name="account_type">
                        <option value=""></option>
                        <option value="7">期权账户余额</option>
                        <option value="1">法币账户余额</option>
                        <option value="3">币币账户余额</option>
                        <option value="5">合约账户余额</option>
                    </select>
                </div>
            </div>
            <div class="layui-inline" style="margin-left: 10px">
                <div class="layui-input-inline">
                    <button class="layui-btn" lay-submit="search" lay-filter="search"><i class="layui-icon">&#xe615;</i></button>
                </div>
            </div>
            



        </form>
       <div style="margin-top:20px">当日充值总金额:<span style="color:red" id="dr">0</span> USDT</div>
       <div>当月充值总金额:<span style="color:red" id="dy">0</span> USDT</div>
    </div>

    <script type="text/html" id="switchTpl">
        <input type="checkbox" name="is_recommend" value="@{{d.id}}" lay-skin="switch" lay-text="是|否" lay-filter="sexDemo" @{{ d.is_recommend == 1 ? 'checked' : '' }}>
    </script>

    <table id="demo" lay-filter="test"></table>
    <script type="text/html" id="barDemo">
    
    <a class="layui-btn layui-btn-xs" lay-event="show">查看</a>
    
    </script>
    <script type="text/html" id="statustml">
        @{{d.status==1 ? '<span class="layui-badge status_bg_1">'+'申请充值'+'</span>' : '' }}
        @{{d.status==2 ? '<span class="layui-badge status_bg_2">'+'充值完成'+'</span>' : '' }}
        @{{d.status==3 ? '<span class="layui-badge status_bg_3">'+'申请失败'+'</span>' : '' }}

    </script>
    <script type="text/html" id="adminhtml">
         @{{d.is_admin==0 ? '用户充值' : '' }}
         @{{d.is_admin==1 ? '人工上分' : '' }}
    </script>
    <script type="text/html" id="accountTypeHtml">
         @{{d.account_type==7 ? '期权账户余额' : '' }}
         @{{d.account_type==1 ? '法币账户余额' : '' }}
         @{{d.account_type==3 || d.account_type== 2 ? '币币账户余额' : '' }}
         @{{d.account_type==5 ? '合约账户余额' : '' }}
    </script>
	<script type="text/html" id="ophtml">
	    <a class="layui-btn layui-btn-xs" lay-event="show">查看</a>
        @{{d.status==1 ? '<button type="button" onclick="pass('+d.id+')">通过</button> <button type="button" onclick="refuse('+d.id+')" data-id='+d.id+' class="btn-refuse">拒绝</button>' : '' }}
        
   

    </script>

    <script type="text/html" id="acc">
       
        <a href="{{$imageServerUrl}}@{{d.user_account}}" target="_blank" >查看</a>
    </script>

@endsection

@section('scripts')
    <script type="text/javascript">
        //显示大图片
        function showBigImage(e) {
            parent.layer.open({
                type: 1,
                title: false,
                closeBtn: 0,
                shadeClose: true, //点击阴影关闭
                area: [$(e).width + 'px', $(e).height + 'px'], //宽高
                content: "<img style='max-width:1400px;max-height:800px' src=" + $(e).attr('src') + " />"
            });
        }
    </script>
    <script>

        layui.use(['table','form'], function(){
            var table = layui.table;
            var $ = layui.jquery;
            var form = layui.form;
            //第一个实例
            table.render({
                elem: '#demo'
                ,url: "{{url('admin/user/charge_list')}}" //数据接口
                ,parseData:function(res){
                    
                    var result = eval("(" + res.extra_data + ")");
                    var dr = result.dr;
                    var dy = result.dy;
                    $("#dr").html(dr);
                    $("#dy").html(dy);
                }
                ,page: true //开启分页
                ,id:'mobileSearch'
                ,cols: [[ //表头
                    {field: 'id', title: 'ID', width:80, sort: true}
                    ,{field: 'account_name', title: '用户名', width:200}
                    ,{field: 'currency_name', title: '虚拟币', width:80}
                    // ,{field: 'user_account', title: '支付账号', minWidth:110}
                    // ,{field: 'user_account', title: '支付凭证', minWidth:110,templet:"#acc"}
                    ,{field: 'user_account', title: '封面图', minWidth:110, templet:"#acc"}
                    // ,{field: 'bank_account', title: '银行卡号', minWidth:80,templet:function(d){
                    //     if(d.type){
                    //         return d.bank_account;
                    //     }else{
                    //         return '';
                    //     }
                    // }}
                    // ,{field: 'address', title: '提币地址', minWidth:100}
                    ,{field: 'amount', title: '数量', minWidth:80}
                    ,{field: 'give', title: '赠送数量', minWidth:80}
                    // ,{field: 'amount', title: '充值金额￥', minWidth:80,templet:function(d){
                    //     let give = 0;
                    //     if(d.give) give = d.give;
                    //     return (d.amount*d.rmb_relation*d.price) + (give*d.rmb_relation*d.price) +"元";
                    // }}
                    // ,{field: 'hes_account', title: '承兑商交易账号', minWidth:180}
                    ,{field: 'is_admin', title: '充值类型', minWidth:100, templet: '#adminhtml'}
                    ,{field: 'account_type', title: '到账账户类型', minWidth:150, templet: '#accountTypeHtml'}
                    ,{field: 'status', title: '交易状态', minWidth:100, templet: '#statustml'}
                    ,{field: 'created_at', title: '充币时间', minWidth:180}
                   
                    ,{title:'操作',minWidth:160,templet: '#ophtml'}

                ]]
            });
            
            form.on('submit(search)', function (data) {
                // data_table.reload({
                //     where: data.field
                //     ,page: {
                //         curr: 1 //重新从第 1 页开始
                //     }
                // });
                // return false;
                var account_number = data.field.account_name;
                var is_admin = data.field.is_admin;
                var account_type = data.field.account_type;
                table.reload('mobileSearch',{
                    where:{account_name:account_number,is_admin:is_admin,account_type:account_type},
                    page: {curr: 1}         //重新从第一页开始
                });
                return false;
            });
            //监听热卖操作
            // form.on('switch(sexDemo)', function(obj){
            //     var id = this.value;
            //     $.ajax({
            //         url:'{{url('admin/product_hot')}}',
            //         type:'post',
            //         dataType:'json',
            //         data:{id:id},
            //         success:function (res) {
            //             if(res.error != 0){
            //                 layer.msg(res.msg);
            //             }
            //         }
            //     });
            // });
            table.on('tool(test)', function(obj){
                var data = obj.data;
                if(obj.event === 'show'){
                    layer_show('确认充值','{{url('admin/user/charge_show')}}?id='+data.id,800,600);
                }
            });

		})
		function pass(id){
        
          $.ajax({
				url:'{{url('admin/user/pass_req')}}',
				type:'post',
				dataType:'json',
				data:{id:id},
				success:function (res) {
                     if(res.type == 'ok'){
                         layer.msg(res.message);
                         setTimeout(function(){
                             window.location.reload(); 
                         },1200)
                     }
                 }
		   })
		}
		   function refuse(id){
          $.ajax({
				url:'{{url('admin/user/refuse_req')}}',
				type:'post',
				dataType:'json',
				data:{id:id},
				success:function (res) {
                   if(res.type == 'ok'){
                         layer.msg(res.message);
                         setTimeout(function(){
                             window.location.reload(); 
                         },1200)
                     }
                 }
		   })
		  }
		   
            //监听提交
            
    </script>

@endsection