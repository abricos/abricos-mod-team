var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['memberList.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.TeamAdminListWidget = Y.Base.create('TeamAdminListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this._wsList = [];

            var memberList = this.get('memberList');
            if (!memberList){
                this.reloadMemberList();
            } else {
                this._onLoadMemberList(memberList);
            }
        },
        destructor: function(){
            this._cleanMemberList();
        },
        _cleanMemberList: function(){
            var list = this._wsList;
            for (var i = 0; i < list.length; i++){
                list[i].destroy();
            }
            return this._wsList = [];
        },
        reloadMemberList: function(){
            var teamApp = this.get('appInstance'),
                filter = {
                    teamid: this.get('team').get('id'),
                    policy: NS.Policy.ADMIN
                };

            this.set('waiting', true);
            teamApp.memberList(filter, function(err, result){
                var memberList = err ? null : result.memberList.get('items');
                this._onLoadMemberList(memberList);
            }, this);
        },
        _onLoadMemberList: function(memberList){
            this.set('waiting', false);
            this.set('memberList', memberList);

            if (!memberList){
                return this.onLoadMemberList(null);
            }

            var appInstance = this.get('appInstance'),
                tp = this.template,
                wsList = this._cleanMemberList();

            memberList.each(function(member){
                var w = new NS.TeamAdminListRowWidget({
                    boundingBox: tp.append('list', tp.replace('itemWrap')),
                    member: member,
                });
                wsList[wsList.length] = w;
            }, this);

            return this.onLoadMemberList(memberList);
        },
        onLoadMemberList: function(memberList){
        },
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,itemWrap'},
            team: NS.ATTRIBUTE.team,
            memberList: {value: null},
        }
    });

    NS.TeamAdminListRowWidget = Y.Base.create('TeamAdminListRowWidget', SYS.AppWidget, [], {}, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'item'},
            member: {value: null}
        },
    });

};
