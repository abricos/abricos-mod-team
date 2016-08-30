var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['base.js']}
    ]
};
Component.entryPoint = function(NS){
    var COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isWrite: 30,
        isView: 10
    });

    SYS.Application.build(COMPONENT, {}, {
        initializer: function(){
            NS.roles.load(function(){
                this.appStructure(function(){
                    this.initCallbackFire();
                }, this);
            }, this);
        },
    }, [], {
        APPS: {
            uprofile: {}
        },
        ATTRS: {
            isLoadAppStructure: {value: false},
            Team: {value: NS.Team},
            TeamList: {value: NS.TeamList},
            Member: {value: NS.Member},
            MemberList: {value: NS.MemberList},
            Config: {value: NS.Config}
        },
        REQS: {
            teamSave: {
                args: ['data']
            },
            team: {
                args: ['teamid'],
                type: 'model:Team',
                onResponse: function(team, data){
                    return function(callback, context){
                        var ownerModule = team.get('module');
                        NS.initApps([ownerModule], function(){
                            var extendApp = Brick.mod[ownerModule].appInstance,
                                teamExtends = team.get('extends');

                            data.extends = data.extends || {};
                            for (var className in data.extends){
                                teamExtends[className] = extendApp.instanceClass(className, data.extends[className]);
                            }

                            var memberList = team.get('members'),
                                userIds = memberList.toArray('userid', {distinct: true});

                            this.getApp('uprofile').userListByIds(userIds, function(err, result){
                                callback.call(context || null);
                            }, context);

                        }, this);
                    };
                }
            },
            teamList: {
                args: ['filter'],
                type: 'modelList:TeamList'
            },
            memberList: {
                args: ['filter'],
                type: 'modelList:MemberList',
                onResponse: function(memberList){
                    var userIds = memberList.toArray('userid', {distinct: true});

                    return function(callback, context){
                        this.getApp('uprofile').userListByIds(userIds, function(err, result){
                            callback.call(context || null);
                        }, context);
                    };
                }
            },
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
        }
    });
};